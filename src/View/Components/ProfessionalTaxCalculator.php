<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

use Crmleaf\Payroll\Calculators\ProfessionalTaxCalculator as Calculator;
use Crmleaf\Payroll\Exceptions\PayrollException;

final class ProfessionalTaxCalculator extends PayrollComponent
{
    public const TOOL = 'professional-tax';

    protected function defaultHeading(): string
    {
        return 'Professional tax calculator';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'monthly_salary',
                'label' => 'Monthly salary',
                'type' => 'money',
                'required' => true,
            ],
            [
                'name' => 'state',
                'label' => 'State',
                'type' => 'select',
                'options' => $this->states(),
                'default' => (string) config('payroll.defaults.state', 'Karnataka'),
                'required' => true,
                'hint' => 'Professional tax is a state levy under article 276; several states levy none at all.',
            ],
            [
                'name' => 'month',
                'label' => 'Calendar month',
                'type' => 'select',
                'options' => [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                ],
                'default' => (int) date('n'),
                'hint' => 'Some schedules charge a different amount in one month - Maharashtra collects ₹300 in '
                    .'February to land on the ₹2,500 annual ceiling.',
            ],
            [
                'name' => 'gender',
                'label' => 'Gender',
                'type' => 'select',
                'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'],
                'default' => 'male',
                'hint' => 'Asked only because a few schedules exempt women below a salary threshold.',
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
            'Payable this month' => 'amount',
            'Annual total' => 'annual_total',
            'Monthly equivalent' => 'monthly_equivalent',
            'Levied in this state' => 'levied',
        ];
    }

    /**
     * The state list comes from the rate table rather than a hardcoded array,
     * so adding a state to the schedule adds it to the dropdown.
     *
     * @return array<string, string>
     */
    private function states(): array
    {
        try {
            $states = Calculator::statesWithProfessionalTax();
        } catch (PayrollException) {
            return [];
        }

        return array_combine($states, $states);
    }
}
