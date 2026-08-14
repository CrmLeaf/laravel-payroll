<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class CtcCalculator extends PayrollComponent
{
    public const TOOL = 'ctc';

    protected function defaultHeading(): string
    {
        return 'CTC breakdown calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'annual_ctc',
                'label' => 'Annual CTC',
                'type' => 'money',
                'required' => true,
                'hint' => 'The whole cost to the company, including the employer\'s PF, ESI and gratuity provision.',
            ],
            [
                'name' => 'state',
                'label' => 'State',
                'type' => 'text',
                'default' => 'Karnataka',
                'hint' => 'Decides which professional tax schedule applies. Several states levy none.',
            ],
            [
                'name' => 'basic_percent',
                'label' => 'Basic as a % of CTC',
                'type' => 'number',
                'default' => 40,
                'step' => '0.5',
                'hint' => 'It drives PF, gratuity and leave encashment, so raising it raises the employer\'s cost.',
            ],
            [
                'name' => 'hra_percent',
                'label' => 'HRA as a % of basic',
                'type' => 'number',
                'default' => 50,
                'step' => '0.5',
                'hint' => '50% in the four metros, 40% elsewhere, per the section 10(13A) exemption limits.',
            ],
            [
                'name' => 'regime',
                'label' => 'Tax regime',
                'type' => 'select',
                'options' => ['new' => 'New regime (115BAC)', 'old' => 'Old regime'],
                'default' => 'new',
            ],
            [
                'name' => 'include_bonus',
                'label' => 'Include statutory bonus in the CTC',
                'type' => 'checkbox',
                'default' => false,
            ],
            [
                'name' => 'include_leave_encashment',
                'label' => 'Include a leave encashment provision',
                'type' => 'checkbox',
                'default' => false,
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
            'Monthly net in hand' => 'net_in_hand',
            'Monthly gross' => 'gross_salary',
            'Monthly deductions' => 'total_deductions',
            'Annual net in hand' => 'annual_net_in_hand',
        ];
    }
}
