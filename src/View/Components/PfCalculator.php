<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class PfCalculator extends PayrollComponent
{
    public const TOOL = 'pf';

    protected function defaultHeading(): string
    {
        return 'EPF contribution calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'basic_salary',
                'label' => 'Basic salary + DA (per month)',
                'type' => 'money',
                'required' => true,
                'hint' => 'EPF is charged on basic wages plus dearness allowance, not on gross pay.',
            ],
            [
                'name' => 'employer_restricts_to_ceiling',
                'label' => 'Employer restricts its share to the ₹15,000 ceiling',
                'type' => 'checkbox',
                'default' => true,
                'hint' => 'Most employers do. The employee\'s own 12% is usually on full basic either way.',
            ],
            [
                'name' => 'eps_eligible',
                'label' => 'Employee is eligible for the Pension Scheme',
                'type' => 'checkbox',
                'default' => true,
                'hint' => 'An employee first joining above ₹15,000 after 1 September 2014 is not.',
            ],
            [
                'name' => 'include_admin_charges',
                'label' => 'Include EPFO administration charges',
                'type' => 'checkbox',
                'default' => true,
            ],
            [
                'name' => 'as_of',
                'label' => 'Rates as on',
                'type' => 'date',
                'hint' => 'Leave blank for today. Set it to recompute an earlier month at the rates in force then.',
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Employee contribution' => 'employee_contribution',
            'Employer - EPF' => 'employer_epf',
            'Employer - EPS' => 'employer_eps',
            'Total monthly' => 'monthly_total',
        ];
    }
}
