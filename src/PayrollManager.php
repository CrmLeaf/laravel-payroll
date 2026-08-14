<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel;

use Crmleaf\Payroll\Calculators\BonusCalculator;
use Crmleaf\Payroll\Calculators\ComplianceCalendar;
use Crmleaf\Payroll\Calculators\CtcCalculator;
use Crmleaf\Payroll\Calculators\EpfoPenaltyCalculator;
use Crmleaf\Payroll\Calculators\EsiCalculator;
use Crmleaf\Payroll\Calculators\FnfCalculator;
use Crmleaf\Payroll\Calculators\GratuityCalculator;
use Crmleaf\Payroll\Calculators\IncomeTaxCalculator;
use Crmleaf\Payroll\Calculators\LeaveEncashmentCalculator;
use Crmleaf\Payroll\Calculators\PfCalculator;
use Crmleaf\Payroll\Calculators\ProfessionalTaxCalculator;
use Crmleaf\Payroll\Calculators\RoiCalculator;
use Crmleaf\Payroll\Calculators\SavingsCalculator;
use Crmleaf\Payroll\Calculators\TdsCalculator;
use Crmleaf\Payroll\Laravel\Documents\InvoiceGenerator;
use Crmleaf\Payroll\Laravel\Documents\PayslipGenerator;
use Crmleaf\Payroll\Rates\RateRepository;
use Illuminate\Contracts\Container\Container;

/**
 * One place to reach every calculator, resolved from the container.
 *
 * The calculators are perfectly usable with `new`, and nothing here stops you
 * doing that. What the manager buys you is a single seam: bind a calculator
 * differently in a test, or swap the rate repository for one pointing at your
 * own tables, and everything reached through the facade picks it up.
 */
final class PayrollManager
{
    public function __construct(private readonly Container $container)
    {
    }

    public function rates(): RateRepository
    {
        return $this->container->make(RateRepository::class);
    }

    public function pf(): PfCalculator
    {
        return $this->container->make(PfCalculator::class);
    }

    public function esi(): EsiCalculator
    {
        return $this->container->make(EsiCalculator::class);
    }

    public function tds(): TdsCalculator
    {
        return $this->container->make(TdsCalculator::class);
    }

    public function incomeTax(): IncomeTaxCalculator
    {
        return $this->container->make(IncomeTaxCalculator::class);
    }

    public function gratuity(): GratuityCalculator
    {
        return $this->container->make(GratuityCalculator::class);
    }

    public function bonus(): BonusCalculator
    {
        return $this->container->make(BonusCalculator::class);
    }

    public function ctc(): CtcCalculator
    {
        return $this->container->make(CtcCalculator::class);
    }

    public function leaveEncashment(): LeaveEncashmentCalculator
    {
        return $this->container->make(LeaveEncashmentCalculator::class);
    }

    public function fnf(): FnfCalculator
    {
        return $this->container->make(FnfCalculator::class);
    }

    public function epfoPenalty(): EpfoPenaltyCalculator
    {
        return $this->container->make(EpfoPenaltyCalculator::class);
    }

    public function professionalTax(): ProfessionalTaxCalculator
    {
        return $this->container->make(ProfessionalTaxCalculator::class);
    }

    public function calendar(): ComplianceCalendar
    {
        return $this->container->make(ComplianceCalendar::class);
    }

    public function roi(): RoiCalculator
    {
        return $this->container->make(RoiCalculator::class);
    }

    public function savings(): SavingsCalculator
    {
        return $this->container->make(SavingsCalculator::class);
    }

    public function payslip(): PayslipGenerator
    {
        return $this->container->make(PayslipGenerator::class);
    }

    public function invoice(): InvoiceGenerator
    {
        return $this->container->make(InvoiceGenerator::class);
    }

    /**
     * Every calculator the manager exposes, keyed by the slug the routes and
     * Blade components use. Handy for a status page, and it is what the
     * package's own tests iterate over so a new tool cannot be half-wired.
     *
     * @return array<string, class-string>
     */
    public static function map(): array
    {
        return [
            'pf' => PfCalculator::class,
            'esi' => EsiCalculator::class,
            'tds' => TdsCalculator::class,
            'income-tax' => IncomeTaxCalculator::class,
            'gratuity' => GratuityCalculator::class,
            'bonus' => BonusCalculator::class,
            'ctc' => CtcCalculator::class,
            'leave-encashment' => LeaveEncashmentCalculator::class,
            'fnf' => FnfCalculator::class,
            'epfo-penalty' => EpfoPenaltyCalculator::class,
            'professional-tax' => ProfessionalTaxCalculator::class,
            'compliance-calendar' => ComplianceCalendar::class,
            'roi' => RoiCalculator::class,
            'savings' => SavingsCalculator::class,
            'payslip' => PayslipGenerator::class,
            'invoice' => InvoiceGenerator::class,
        ];
    }
}
