<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class RoiCalculator extends PayrollComponent
{
    public const TOOL = 'roi';

    protected function defaultHeading(): string
    {
        return 'Payroll automation ROI calculator';
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
                'name' => 'hourly_rate',
                'label' => 'Fully loaded hourly cost of the payroll staff',
                'type' => 'money',
                'required' => true,
                'hint' => 'Salary plus the employer\'s own PF, ESI and gratuity provision - not take-home over hours.',
            ],
            [
                'name' => 'hours_per_cycle_manual',
                'label' => 'Hours per payroll cycle today',
                'type' => 'number',
                'required' => true,
                'step' => '0.5',
            ],
            [
                'name' => 'cycles_per_year',
                'label' => 'Payroll cycles a year',
                'type' => 'integer',
                'default' => 12,
            ],
            [
                'name' => 'automation_efficiency',
                'label' => 'Share of manual hours automation removes',
                'type' => 'number',
                'default' => 0.8,
                'step' => '0.05',
                'min' => 0,
                'max' => 1,
                'hint' => 'Between 0 and 1. Somebody still approves the run, so 1 is not honest.',
            ],
            [
                'name' => 'software_annual_cost',
                'label' => 'Annual software cost',
                'type' => 'money',
            ],
            [
                'name' => 'penalty_cost_per_year',
                'label' => 'Late-filing penalties a year',
                'type' => 'money',
            ],
            [
                'name' => 'error_cost_per_year',
                'label' => 'Cost of payroll errors a year',
                'type' => 'money',
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Net benefit a year' => 'net_benefit',
            'Annual saving' => 'annual_saving',
            'ROI' => 'roi_percent',
            'Payback (months)' => 'payback_months',
        ];
    }
}
