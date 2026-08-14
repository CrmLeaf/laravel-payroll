<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class TdsCalculator extends PayrollComponent
{
    public const TOOL = 'tds';

    protected function defaultHeading(): string
    {
        return 'Monthly TDS calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'monthly_gross',
                'label' => 'Monthly gross salary',
                'type' => 'money',
                'required' => true,
            ],
            [
                'name' => 'regime',
                'label' => 'Tax regime',
                'type' => 'select',
                'options' => ['new' => 'New regime (115BAC)', 'old' => 'Old regime'],
                'default' => 'new',
                'hint' => 'The new regime is the default regime since FY 2023-24 unless the employee opts out.',
            ],
            [
                'name' => 'age',
                'label' => 'Age',
                'type' => 'integer',
                'default' => 30,
                'hint' => 'Only affects the old regime, where the basic exemption limit rises at 60 and again at 80.',
            ],
            [
                'name' => 'months_remaining',
                'label' => 'Months remaining in the financial year',
                'type' => 'integer',
                'default' => 12,
                'hint' => 'Section 192 spreads the year\'s liability over the months left.',
            ],
            [
                'name' => 'tax_already_deducted',
                'label' => 'TDS already deducted this year',
                'type' => 'money',
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
            'Monthly TDS' => 'monthly_tds',
            'Annual tax' => 'annual_tax',
            'Balance for the year' => 'balance_tax',
            'Annual gross' => 'annual_gross',
        ];
    }
}
