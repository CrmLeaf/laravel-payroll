<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class FnfCalculator extends PayrollComponent
{
    public const TOOL = 'fnf';

    protected function defaultHeading(): string
    {
        return 'Full and final settlement calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'monthly_gross',
                'label' => 'Monthly gross',
                'type' => 'money',
                'required' => true,
            ],
            [
                'name' => 'monthly_basic',
                'label' => 'Monthly basic + DA',
                'type' => 'money',
                'required' => true,
                'hint' => 'The base for gratuity, leave encashment, PF and notice pay.',
            ],
            [
                'name' => 'joining_date',
                'label' => 'Date of joining',
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'last_working_date',
                'label' => 'Last working date',
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'unpaid_days',
                'label' => 'Loss-of-pay days in the final month',
                'type' => 'integer',
                'default' => 0,
            ],
            [
                'name' => 'leave_days_balance',
                'label' => 'Earned leave balance (days)',
                'type' => 'integer',
                'default' => 0,
            ],
            [
                'name' => 'notice_period_days',
                'label' => 'Contractual notice period (days)',
                'type' => 'integer',
                'default' => 0,
            ],
            [
                'name' => 'notice_days_served',
                'label' => 'Notice days actually served',
                'type' => 'integer',
                'default' => 0,
                'hint' => 'Any shortfall is recovered at basic pay, not gross.',
            ],
            [
                'name' => 'other_earnings',
                'label' => 'Other earnings (incentive, arrears)',
                'type' => 'money',
            ],
            [
                'name' => 'recoveries',
                'label' => 'Recoveries (assets, loans, advances)',
                'type' => 'money',
            ],
            [
                'name' => 'separation_reason',
                'label' => 'Reason for separation',
                'type' => 'select',
                'options' => [
                    'resignation' => 'Resignation',
                    'retirement' => 'Retirement',
                    'superannuation' => 'Superannuation',
                    'termination' => 'Termination',
                    'retrenchment' => 'Retrenchment',
                    'death' => 'Death',
                    'disablement' => 'Disablement',
                ],
                'default' => 'resignation',
            ],
            [
                'name' => 'state',
                'label' => 'State',
                'type' => 'text',
                'default' => 'Karnataka',
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
            'Net payable' => 'net_payable',
            'Gross earnings' => 'gross_earnings',
            'Total deductions' => 'total_deductions',
            'Gratuity' => 'gratuity',
        ];
    }
}
