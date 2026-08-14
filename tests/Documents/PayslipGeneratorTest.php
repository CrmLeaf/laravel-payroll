<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests\Documents;

use Crmleaf\Payroll\Calculators\CtcCalculator;
use Crmleaf\Payroll\Exceptions\InvalidInputException;
use Crmleaf\Payroll\Laravel\Documents\PayslipGenerator;
use Crmleaf\Payroll\Laravel\Support\IndianNumber;
use Crmleaf\Payroll\Laravel\Tests\TestCase;
use Crmleaf\Payroll\Money;
use PHPUnit\Framework\Attributes\DataProvider;

final class PayslipGeneratorTest extends TestCase
{
    private function generator(): PayslipGenerator
    {
        return $this->app->make(PayslipGenerator::class);
    }

    public function testItTotalsEarningsAndDeductionsAndNetsThem(): void
    {
        $data = $this->generator()->generate(
            employee: ['name' => 'R Iyer'],
            month: '2025-04',
            earnings: ['Basic' => 30000, 'HRA' => 15000, 'Special allowance' => 5000],
            deductions: ['Provident Fund' => 3600, 'Professional Tax' => 200],
        )->toArray();

        $this->assertSame(50000.0, $data['gross_earnings']->toRupees());
        $this->assertSame(3800.0, $data['total_deductions']->toRupees());
        $this->assertSame(46200.0, $data['net_pay']->toRupees());
        $this->assertSame('Rupees Forty Six Thousand Two Hundred Only', $data['net_pay_in_words']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function months(): array
    {
        return [
            'iso month' => ['2025-04', 'April 2025', '2025-04'],
            'written month' => ['April 2025', 'April 2025', '2025-04'],
            'mid-month date' => ['2025-04-17', 'April 2025', '2025-04'],
        ];
    }

    #[DataProvider('months')]
    public function testTheWagePeriodIsPinnedToTheCalendarMonth(string $input, string $label, string $key): void
    {
        $data = $this->generator()->generate(
            employee: ['name' => 'R Iyer'],
            month: $input,
            earnings: ['Basic' => 30000],
        )->toArray();

        $this->assertSame($label, $data['month']);
        $this->assertSame($key, $data['month_key']);
        $this->assertSame('2025-04-01', $data['period_start']->format('Y-m-d'));
        $this->assertSame('2025-04-30', $data['period_end']->format('Y-m-d'));
    }

    public function testAnUnparseableMonthIsACallerError(): void
    {
        $this->expectException(InvalidInputException::class);

        $this->generator()->generate(
            employee: ['name' => 'R Iyer'],
            month: 'sometime last spring',
            earnings: ['Basic' => 30000],
        );
    }

    public function testAPayslipWithNoEarningsIsRefused(): void
    {
        $this->expectException(InvalidInputException::class);

        $this->generator()->generate(employee: ['name' => 'R Iyer'], month: '2025-04', earnings: []);
    }

    public function testTheFilenameIsSafeForAContentDispositionHeader(): void
    {
        $document = $this->generator()->generate(
            employee: ['name' => 'R. Iyer & Co/Sons'],
            month: '2025-04',
            earnings: ['Basic' => 30000],
        );

        $this->assertSame('payslip-r-iyer-co-sons-2025-04.pdf', $document->filename);
    }

    public function testFromCtcTakesTheMonthlyFiguresWithoutDividingThemAgain(): void
    {
        $ctc = $this->app->make(CtcCalculator::class)->calculate(
            annualCtc: Money::fromRupees(1_200_000),
            state: 'Karnataka',
            asOf: $this->ratedDate(),
        );

        $data = $this->generator()->fromCtc(
            ctc: $ctc,
            employee: ['name' => 'R Iyer'],
            month: '2025-04',
        )->toArray();

        $earnings = array_column(array_map(
            static fn (array $row) => ['label' => $row['label'], 'amount' => $row['amount']->toRupees()],
            $data['earnings'],
        ), 'amount', 'label');

        $this->assertSame($ctc->basic->toRupees(), $earnings['Basic']);
        $this->assertSame($ctc->hra->toRupees(), $earnings['House Rent Allowance']);
        $this->assertSame($ctc->grossSalary->toRupees(), $data['gross_earnings']->toRupees());
    }

    public function testFromCtcShowsTheEmployerSideSeparatelyFromTheDeductions(): void
    {
        $ctc = $this->app->make(CtcCalculator::class)->calculate(annualCtc: Money::fromRupees(1_200_000), asOf: $this->ratedDate());

        $data = $this->generator()->fromCtc(
            ctc: $ctc,
            employee: ['name' => 'R Iyer'],
            month: '2025-04',
        )->toArray();

        $labels = array_column($data['deductions'], 'label');

        // The employer's contribution is its cost, not the employee's
        // deduction, and netting it off the take-home is the single most
        // common payslip error this package exists to avoid.
        $this->assertNotContains('Provident Fund (employer)', $labels);
        $this->assertNotEmpty($data['employer_contributions']);
        $this->assertSame($ctc->netInHand->toRupees(), $data['net_pay']->toRupees());
    }

    public function testFromCtcCarriesTheWorkingAndItsCitations(): void
    {
        $ctc = $this->app->make(CtcCalculator::class)->calculate(annualCtc: Money::fromRupees(900_000), asOf: $this->ratedDate());

        $html = $this->generator()->fromCtc(
            ctc: $ctc,
            employee: ['name' => 'R Iyer'],
            month: '2025-04',
        )->html();

        $this->assertStringContainsString('How these figures were arrived at', $html);
        // Blade escapes the apostrophe in the statute's name, so the assertion
        // deliberately straddles neither side of it.
        $this->assertStringContainsString('Provident Funds and Miscellaneous Provisions Act, 1952', $html);
    }

    public function testTheRenderedSlipCarriesTheStatutoryIdentifiers(): void
    {
        $html = $this->generator()->generate(
            employee: [
                'name' => 'R Iyer',
                'code' => 'EMP-0042',
                'pan' => 'ABCDE1234F',
                'uan' => '100123456789',
                'esic_number' => '12345678901234567',
            ],
            month: '2025-04',
            earnings: ['Basic' => 30000],
            attendance: ['working_days' => 30, 'paid_days' => 28, 'lop_days' => 2],
        )->html();

        foreach (['EMP-0042', 'ABCDE1234F', '100123456789', '12345678901234567', '28', 'Net pay'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function amounts(): array
    {
        return [
            'thousands' => [46200, 'Rupees Forty Six Thousand Two Hundred Only'],
            'lakhs' => [1234567, 'Rupees Twelve Lakh Thirty Four Thousand Five Hundred Sixty Seven Only'],
            'crores' => [25000000, 'Rupees Two Crore Fifty Lakh Only'],
            'with paise' => [1250.5, 'Rupees One Thousand Two Hundred Fifty and Fifty Paise Only'],
            'nought' => [0, 'Rupees Zero Only'],
        ];
    }

    #[DataProvider('amounts')]
    public function testAmountsAreSpeltOutWithLakhsAndCrores(float $amount, string $words): void
    {
        $this->assertSame($words, IndianNumber::toWords(Money::fromRupees($amount)));
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function groupings(): array
    {
        return [
            'below a lakh' => [46200, '46,200.00'],
            'lakhs' => [1234567, '12,34,567.00'],
            'crores' => [250000000, '25,00,00,000.00'],
        ];
    }

    #[DataProvider('groupings')]
    public function testNumbersGroupTheIndianWay(float $amount, string $expected): void
    {
        $this->assertSame($expected, IndianNumber::group($amount));
    }
}
