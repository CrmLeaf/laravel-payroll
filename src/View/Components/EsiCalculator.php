<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class EsiCalculator extends PayrollComponent
{
    public const TOOL = 'esi';

    protected function defaultHeading(): string
    {
        return 'ESI contribution calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'gross_wages',
                'label' => 'Gross wages (per month)',
                'type' => 'money',
                'required' => true,
                'hint' => 'Wages as defined in section 2(22): basic, allowances and overtime.',
            ],
            [
                'name' => 'continuing_from_prior_period',
                'label' => 'Already contributing at the start of this contribution period',
                'type' => 'checkbox',
                'default' => false,
                'hint' => 'Crossing the ₹21,000 limit mid-period does not stop contributions - they run to the '
                    .'end of the period under regulation 4.',
            ],
            [
                'name' => 'disabled',
                'label' => 'Employee is a person with disability',
                'type' => 'checkbox',
                'default' => false,
                'hint' => 'The wage limit is higher for employees with disability.',
            ],
            [
                'name' => 'average_daily_wage',
                'label' => 'Average daily wage',
                'type' => 'money',
                'hint' => 'Optional. Below the daily threshold the employee share is waived and only the '
                    .'employer contributes.',
            ],
            [
                'name' => 'as_of',
                'label' => 'Rates as on',
                'type' => 'date',
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Employee contribution' => 'employee_contribution',
            'Employer contribution' => 'employer_contribution',
            'Total' => 'total_contribution',
            'ESI applicable' => 'applicable',
        ];
    }
}
