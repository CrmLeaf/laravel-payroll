<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class IncomeTaxCalculator extends PayrollComponent
{
    public const TOOL = 'income-tax';

    protected function defaultHeading(): string
    {
        return 'Income tax calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'gross_salary',
                'label' => 'Annual gross salary',
                'type' => 'money',
                'required' => true,
            ],
            [
                'name' => 'regime',
                'label' => 'Tax regime',
                'type' => 'select',
                'options' => ['new' => 'New regime (115BAC)', 'old' => 'Old regime'],
                'default' => 'new',
            ],
            [
                'name' => 'age',
                'label' => 'Age',
                'type' => 'integer',
                'default' => 30,
            ],
            [
                'name' => 'other_income',
                'label' => 'Other income',
                'type' => 'money',
                'hint' => 'Interest, rent and anything else the employee has declared to the employer.',
            ],
            [
                'name' => 'deductions[80c]',
                'label' => 'Section 80C',
                'type' => 'money',
                'hint' => 'Old regime only. Chapter VI-A deductions are almost entirely withdrawn under the new one.',
            ],
            [
                'name' => 'deductions[80d]',
                'label' => 'Section 80D (health insurance)',
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
            'Total tax' => 'total_tax',
            'Taxable income' => 'taxable_income',
            'Rebate under 87A' => 'rebate',
            'Effective rate' => 'effective_rate',
        ];
    }
}
