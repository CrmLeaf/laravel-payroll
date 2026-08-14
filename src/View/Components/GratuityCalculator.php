<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class GratuityCalculator extends PayrollComponent
{
    public const TOOL = 'gratuity';

    protected function defaultHeading(): string
    {
        return 'Gratuity calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'last_drawn_salary',
                'label' => 'Last drawn basic + DA (per month)',
                'type' => 'money',
                'required' => true,
                'hint' => 'Section 2(s) excludes HRA, bonus, overtime and other allowances from "wages".',
            ],
            [
                'name' => 'years_of_service',
                'label' => 'Completed years of service',
                'type' => 'integer',
                'required' => true,
            ],
            [
                'name' => 'months_of_service',
                'label' => 'Additional months',
                'type' => 'integer',
                'default' => 0,
                'hint' => 'A part-year above six months rounds the service up by a whole year.',
            ],
            [
                'name' => 'covered',
                'label' => 'Establishment is covered by the Payment of Gratuity Act',
                'type' => 'checkbox',
                'default' => true,
                'hint' => 'Covered divides by 26 working days; non-covered by 30 calendar days.',
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
                'hint' => 'Death and disablement waive the five-year qualifying period under section 4(1).',
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
            'Gratuity payable' => 'gratuity',
            'Exempt from tax' => 'tax_exempt',
            'Taxable' => 'taxable',
            'Completed years counted' => 'completed_years',
        ];
    }
}
