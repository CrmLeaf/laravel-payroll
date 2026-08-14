<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class ProfessionalTaxRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monthly_salary' => self::rupees(),
            'state' => ['required', 'string', 'max:80'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'liable_to_income_tax' => ['sometimes', 'boolean'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'state.required' => 'A state is required. Professional tax is levied by states under article 276 of the '
                .'Constitution, not by the Union, so there is no national rate - and several states and union '
                .'territories levy none at all.',
            'month.min' => 'The month must be 1 to 12, as a calendar month number. It matters because some states '
                .'charge a different amount in one month of the year - Maharashtra collects ₹300 in February against '
                .'₹200 in the other eleven, to land on the ₹2,500 annual ceiling article 276 imposes.',
            'month.max' => 'The month must be 1 to 12, as a calendar month number.',
            'gender.in' => 'Gender must be male, female or other. It is asked because a few schedules exempt women '
                .'below a salary threshold; it has no other effect.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'monthlySalary' => $this->money('monthly_salary'),
            'state' => $this->inputString('state', (string) $this->configValue('defaults.state', 'Karnataka')),
            'month' => $this->inputInt('month', (int) date('n')),
            'gender' => $this->inputString('gender', 'male'),
            'liableToIncomeTax' => $this->inputBool('liable_to_income_tax', true),
            'asOf' => $this->asOf(),
        ];
    }
}
