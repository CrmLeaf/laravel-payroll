<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class BonusCalculator extends PayrollComponent
{
    public const TOOL = 'bonus';

    protected function defaultHeading(): string
    {
        return 'Statutory bonus calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'monthly_wages',
                'label' => 'Monthly wages (basic + DA)',
                'type' => 'money',
                'required' => true,
                'hint' => 'Above the ₹21,000 eligibility limit no statutory bonus is payable at all.',
            ],
            [
                'name' => 'bonus_rate',
                'label' => 'Bonus rate (%)',
                'type' => 'number',
                'default' => 8.33,
                'step' => '0.01',
                'min' => 8.33,
                'max' => 20,
                'hint' => 'Between 8.33% and 20% - the minimum under section 10 and the maximum under section 11.',
            ],
            [
                'name' => 'months_worked',
                'label' => 'Months worked in the accounting year',
                'type' => 'integer',
                'default' => 12,
            ],
            [
                'name' => 'days_worked',
                'label' => 'Days worked in the part month',
                'type' => 'integer',
                'default' => 30,
            ],
            [
                'name' => 'minimum_wage',
                'label' => 'Applicable minimum wage',
                'type' => 'money',
                'hint' => 'Optional. Where the notified minimum wage exceeds the ₹7,000 calculation ceiling, the '
                    .'higher figure is the base.',
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
            'Bonus payable' => 'bonus',
            'Per month' => 'monthly_bonus',
            'Eligible' => 'eligible',
            'Calculation ceiling' => 'calculation_ceiling',
        ];
    }
}
