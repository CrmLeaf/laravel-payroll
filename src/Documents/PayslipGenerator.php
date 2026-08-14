<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Exceptions\InvalidInputException;
use Crmleaf\Payroll\Laravel\Support\IndianNumber;
use Crmleaf\Payroll\Money;
use Crmleaf\Payroll\Results\CtcResult;

/**
 * A monthly payslip, rendered in your application against your config.
 *
 * The payslip is a statutory record, not a receipt: rule 26 of the Payment of
 * Wages (Central) Rules and the corresponding state shops-and-establishments
 * rules require the wage period, the wages earned, every deduction with its
 * head, and the net paid. So the generator refuses to invent numbers - it lays
 * out what you give it and totals it - with one exception, `fromCtc()`, where
 * the numbers come from a calculation that can show its own working.
 */
final class PayslipGenerator extends DocumentGenerator
{
    protected function view(): string
    {
        return 'payroll::documents.payslip';
    }

    /**
     * @param array<string, mixed> $employee name, code, designation, department, location,
     *                                       pan, uan, pf_number, esic_number, bank_account,
     *                                       bank_name, ifsc, date_of_joining
     * @param array<string, Money|int|float|string> $earnings label => amount, in display order
     * @param array<string, Money|int|float|string> $deductions label => amount, in display order
     * @param array<string, mixed> $attendance paid_days, lop_days, working_days, leave_balance
     * @param array<string, mixed> $company overrides the payroll.company config for this slip
     * @param array<int, string> $notes printed under the totals
     */
    public function generate(
        array $employee,
        string $month,
        array $earnings,
        array $deductions = [],
        array $attendance = [],
        array $company = [],
        array $notes = [],
        ?string $currency = 'INR',
    ): Document {
        if ($earnings === []) {
            throw InvalidInputException::outOfRange('Payslip earnings', 'at least one earning head', 0);
        }

        $name = trim((string) ($employee['name'] ?? ''));

        if ($name === '') {
            throw InvalidInputException::outOfRange('Employee name', 'a non-empty string', $name);
        }

        $earningRows = $this->rows($earnings);
        $deductionRows = $this->rows($deductions);

        $grossEarnings = $this->sum($earningRows);
        $totalDeductions = $this->sum($deductionRows);
        $net = $grossEarnings->subtract($totalDeductions);

        $period = $this->period($month);

        $data = [
            'employee' => $employee,
            'company' => $this->company($company),
            'currency' => $currency ?? 'INR',
            'month' => $period['label'],
            'month_key' => $period['key'],
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'attendance' => $attendance + [
                'working_days' => (int) $period['start']->format('t'),
                'paid_days' => null,
                'lop_days' => 0,
            ],
            'earnings' => $earningRows,
            'deductions' => $deductionRows,
            'gross_earnings' => $grossEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay' => $net,
            'net_pay_in_words' => IndianNumber::toWords($net),
            'employer_contributions' => [],
            'workings' => [],
            'notes' => array_values(array_filter($notes)),
        ];

        return $this->document($data, sprintf(
            'payslip-%s-%s.pdf',
            self::slug($name),
            $period['key'],
        ));
    }

    /**
     * Build a payslip straight from a CTC breakdown.
     *
     * This is the honest version of the "generate a payslip" demo: every head
     * on the slip comes from a calculation that carries its statute with it, so
     * the working printed at the foot of the slip is the actual working and not
     * a caption. Real payroll has attendance, arrears and reimbursements that a
     * CTC breakdown knows nothing about, which is why the earnings and
     * deductions are still overridable.
     *
     * @param array<string, mixed> $employee
     * @param array<string, Money|int|float|string> $extraEarnings arrears, incentives, reimbursements
     * @param array<string, Money|int|float|string> $extraDeductions loan instalments, advances
     * @param array<string, mixed> $attendance
     * @param array<string, mixed> $company
     */
    public function fromCtc(
        CtcResult $ctc,
        array $employee,
        string $month,
        array $extraEarnings = [],
        array $extraDeductions = [],
        array $attendance = [],
        array $company = [],
        bool $showWorking = true,
    ): Document {
        // Every figure on a CtcResult bar `annualCtc` is already monthly, which
        // is exactly the period a payslip covers, so nothing is divided here.
        $earnings = array_filter([
            'Basic' => $ctc->basic,
            'House Rent Allowance' => $ctc->hra,
            'Special Allowance' => $ctc->specialAllowance,
        ], static fn (Money $amount) => $amount->isPositive());

        $deductions = array_filter([
            'Provident Fund (employee)' => $ctc->employeePf,
            'ESI (employee)' => $ctc->employeeEsi,
            'Professional Tax' => $ctc->professionalTax,
            'TDS' => $ctc->tds,
        ], static fn (Money $amount) => $amount->isPositive());

        $document = $this->generate(
            employee: $employee,
            month: $month,
            earnings: $earnings + $extraEarnings,
            deductions: $deductions + $extraDeductions,
            attendance: $attendance,
            company: $company,
        );

        // Rebuild with the employer side and the working attached. The
        // employer's contributions are not a deduction and must never be
        // netted off the take-home, but leaving them off a slip built from a
        // CTC invites the "where did the rest of my CTC go?" question the
        // whole library exists to answer.
        $data = $document->toArray();
        $data['employer_contributions'] = $this->rows(array_filter([
            'Provident Fund (employer)' => $ctc->employerPf,
            'ESI (employer)' => $ctc->employerEsi,
            'Gratuity provision' => $ctc->gratuityProvision,
        ], static fn (Money $amount) => $amount->isPositive()));
        $data['annual_ctc'] = $ctc->annualCtc;

        if ($showWorking) {
            $data['workings'] = array_map(
                static fn ($step) => $step->jsonSerialize(),
                $ctc->steps(),
            );
            $data['citations'] = $ctc->citations();
        }

        return $this->document($data, $document->filename);
    }

    /**
     * @param array<string, Money|int|float|string> $amounts
     *
     * @return array<int, array{label: string, amount: Money}>
     */
    private function rows(array $amounts): array
    {
        $rows = [];

        foreach ($amounts as $label => $amount) {
            $rows[] = ['label' => (string) $label, 'amount' => self::money($amount)];
        }

        return $rows;
    }

    /**
     * @param array<int, array{label: string, amount: Money}> $rows
     */
    private function sum(array $rows): Money
    {
        $total = Money::zero();

        foreach ($rows as $row) {
            $total = $total->add($row['amount']);
        }

        return $total;
    }

    /**
     * Accepts '2025-04', 'April 2025' or any parseable date, and pins the wage
     * period to that calendar month.
     *
     * @return array{key: string, label: string, start: \DateTimeImmutable, end: \DateTimeImmutable}
     */
    private function period(string $month): array
    {
        try {
            $date = new \DateTimeImmutable(preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month.'-01' : $month);
        } catch (\Exception) {
            throw InvalidInputException::outOfRange(
                'Payslip month',
                'a month such as "2025-04" or "April 2025"',
                $month,
            );
        }

        $start = $date->modify('first day of this month')->setTime(0, 0);

        return [
            'key' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
            'start' => $start,
            'end' => $start->modify('last day of this month'),
        ];
    }
}
