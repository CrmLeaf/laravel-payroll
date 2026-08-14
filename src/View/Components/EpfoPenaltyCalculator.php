<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class EpfoPenaltyCalculator extends PayrollComponent
{
    public const TOOL = 'epfo-penalty';

    protected function defaultHeading(): string
    {
        return 'EPFO late payment penalty calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'contribution_amount',
                'label' => 'Contribution in arrears',
                'type' => 'money',
                'required' => true,
                'hint' => 'The whole remittance as it appears on the challan - EPF, EPS and EDLI together.',
            ],
            [
                'name' => 'wage_month',
                'label' => 'Wage month',
                'type' => 'month',
                'required' => true,
                'hint' => 'The month the wages relate to. April\'s contributions fall due by 15 May.',
            ],
            [
                'name' => 'actual_payment_date',
                'label' => 'Date the challan was paid',
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'as_of',
                'label' => 'Rates as on',
                'type' => 'date',
                'hint' => 'The basis for damages under section 14B changed in June 2024; this decides which applies.',
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Interest under 7Q' => 'interest',
            'Damages under 14B' => 'damages',
            'Total payable' => 'total',
            'Days late' => 'delay_days',
        ];
    }
}
