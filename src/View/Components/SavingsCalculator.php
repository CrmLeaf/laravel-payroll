<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class SavingsCalculator extends PayrollComponent
{
    public const TOOL = 'savings';

    protected function defaultHeading(): string
    {
        return 'Payroll provider savings calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'employee_count',
                'label' => 'Employees on payroll',
                'type' => 'integer',
                'required' => true,
            ],
            [
                'name' => 'current_per_employee_per_month',
                'label' => 'Current cost per employee per month',
                'type' => 'money',
                'required' => true,
                'hint' => 'Take it from the invoice, not the price list.',
            ],
            [
                'name' => 'proposed_per_employee_per_month',
                'label' => 'Proposed cost per employee per month',
                'type' => 'money',
                'required' => true,
            ],
            [
                'name' => 'current_annual_platform_fee',
                'label' => 'Current annual platform fee',
                'type' => 'money',
                'hint' => 'Headline per-employee pricing rarely includes it, which is why it is separate.',
            ],
            [
                'name' => 'proposed_annual_platform_fee',
                'label' => 'Proposed annual platform fee',
                'type' => 'money',
            ],
            [
                'name' => 'migration_cost',
                'label' => 'One-off migration cost',
                'type' => 'money',
            ],
            [
                'name' => 'contract_months',
                'label' => 'Contract length (months)',
                'type' => 'integer',
                'default' => 12,
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Annual saving' => 'annual_saving',
            'First-year saving' => 'first_year_saving',
            'Over the contract' => 'contract_term_saving',
            'Break-even (months)' => 'break_even_months',
        ];
    }
}
