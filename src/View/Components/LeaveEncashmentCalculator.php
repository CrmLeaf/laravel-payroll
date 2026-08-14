<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class LeaveEncashmentCalculator extends PayrollComponent
{
    public const TOOL = 'leave-encashment';

    protected function defaultHeading(): string
    {
        return 'Leave encashment calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'average_monthly_salary',
                'label' => 'Average monthly salary (basic + DA)',
                'type' => 'money',
                'required' => true,
                'hint' => 'Averaged over the ten months immediately before separation.',
            ],
            [
                'name' => 'leave_days_encashed',
                'label' => 'Leave days encashed',
                'type' => 'integer',
                'required' => true,
            ],
            [
                'name' => 'years_of_service',
                'label' => 'Completed years of service',
                'type' => 'integer',
                'required' => true,
                'hint' => 'The exemption allows 30 days of leave for each completed year.',
            ],
            [
                'name' => 'government_employee',
                'label' => 'Central or state government employee',
                'type' => 'checkbox',
                'default' => false,
                'hint' => 'Government employees have encashment on retirement wholly exempt.',
            ],
            [
                'name' => 'during_service',
                'label' => 'Encashed while still in service',
                'type' => 'checkbox',
                'default' => false,
                'hint' => 'Fully taxable - the section 10(10AA) exemption applies only on separation.',
            ],
            [
                'name' => 'exemption_already_claimed',
                'label' => 'Exemption already claimed from earlier employers',
                'type' => 'money',
                'hint' => 'The statutory limit is a lifetime one across all employers.',
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
            'Encashment' => 'encashment',
            'Exempt' => 'exemption',
            'Taxable' => 'taxable',
            'Binding limb' => 'binding_limb',
        ];
    }
}
