<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class IncomeTaxRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gross_salary' => self::rupees(),
            'regime' => ['sometimes', 'string', 'in:new,old'],
            'age' => ['sometimes', 'integer', 'min:0', 'max:120'],
            'deductions' => ['sometimes', 'array'],
            'deductions.*' => ['numeric', 'min:0'],
            'other_income' => self::rupees(false),
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'regime.in' => 'The regime must be "new" or "old".',
            'age.max' => 'Age must be 120 or below. It matters because the old regime\'s basic exemption limit '
                .'rises for senior citizens at 60 and again for very senior citizens at 80.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        /** @var array<string, mixed> $deductions */
        $deductions = $this->input('deductions', []);

        return [
            'grossSalary' => $this->money('gross_salary'),
            'regime' => $this->inputString('regime', (string) $this->configValue('defaults.regime', 'new')),
            'age' => $this->inputInt('age', 30),
            'deductions' => is_array($deductions) ? $deductions : [],
            'otherIncome' => $this->optionalMoney('other_income'),
            'asOf' => $this->asOf(),
        ];
    }
}
