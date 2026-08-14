<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class PfRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'basic_salary' => self::rupees(),
            'employer_restricts_to_ceiling' => ['sometimes', 'boolean'],
            'eps_eligible' => ['sometimes', 'boolean'],
            'age' => ['nullable', 'integer', 'min:14', 'max:100'],
            'reduced_rate' => ['sometimes', 'boolean'],
            'include_admin_charges' => ['sometimes', 'boolean'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'basic_salary.required' => 'Basic salary is required. EPF is computed on basic wages plus dearness '
                .'allowance, not on gross pay - passing gross here overstates every contribution.',
            'age.min' => 'The Child Labour (Prohibition and Regulation) Act bars employment below 14, so an age '
                .'under 14 is a data-entry error rather than an unusual case.',
            'age.max' => 'Age must be 100 or below.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'basicSalary' => $this->money('basic_salary'),
            'employerRestrictsToCeiling' => $this->inputBool('employer_restricts_to_ceiling', true),
            'epsEligible' => $this->inputBool('eps_eligible', true),
            'age' => $this->has('age') ? $this->inputInt('age') : null,
            'reducedRate' => $this->inputBool('reduced_rate', false),
            'includeAdminCharges' => $this->inputBool('include_admin_charges', true),
            'asOf' => $this->asOf(),
        ];
    }
}
