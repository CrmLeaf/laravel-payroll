<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Facades;

use Crmleaf\Payroll\Laravel\PayrollManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Crmleaf\Payroll\Rates\RateRepository rates()
 * @method static \Crmleaf\Payroll\Calculators\PfCalculator pf()
 * @method static \Crmleaf\Payroll\Calculators\EsiCalculator esi()
 * @method static \Crmleaf\Payroll\Calculators\TdsCalculator tds()
 * @method static \Crmleaf\Payroll\Calculators\IncomeTaxCalculator incomeTax()
 * @method static \Crmleaf\Payroll\Calculators\GratuityCalculator gratuity()
 * @method static \Crmleaf\Payroll\Calculators\BonusCalculator bonus()
 * @method static \Crmleaf\Payroll\Calculators\CtcCalculator ctc()
 * @method static \Crmleaf\Payroll\Calculators\LeaveEncashmentCalculator leaveEncashment()
 * @method static \Crmleaf\Payroll\Calculators\FnfCalculator fnf()
 * @method static \Crmleaf\Payroll\Calculators\EpfoPenaltyCalculator epfoPenalty()
 * @method static \Crmleaf\Payroll\Calculators\ProfessionalTaxCalculator professionalTax()
 * @method static \Crmleaf\Payroll\Calculators\ComplianceCalendar calendar()
 * @method static \Crmleaf\Payroll\Calculators\RoiCalculator roi()
 * @method static \Crmleaf\Payroll\Calculators\SavingsCalculator savings()
 * @method static \Crmleaf\Payroll\Laravel\Documents\PayslipGenerator payslip()
 * @method static \Crmleaf\Payroll\Laravel\Documents\InvoiceGenerator invoice()
 *
 * @see PayrollManager
 */
final class Payroll extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayrollManager::class;
    }
}
