<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class TdsRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monthly_gross' => self::rupees(),
            'regime' => ['sometimes', 'string', 'in:new,old'],
            'age' => ['sometimes', 'integer', 'min:0', 'max:120'],
            'deductions' => ['sometimes', 'array'],
            'deductions.*' => ['numeric', 'min:0'],
            'months_remaining' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'tax_already_deducted' => self::rupees(false),
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'regime.in' => 'The regime must be "new" or "old". The new regime under section 115BAC is the default '
                .'regime since FY 2023-24; the old one applies only where the employee has opted out.',
            'months_remaining.min' => 'Months remaining must be at least 1. Section 192 spreads the year\'s tax over '
                .'the months left in the financial year, so a value of nought has nothing to spread it across.',
            'months_remaining.max' => 'Months remaining cannot exceed 12 - an Indian financial year runs April to March.',
            'deductions.*.min' => 'A deduction cannot be negative. Chapter VI-A deductions reduce taxable income; '
                .'to add income instead, use other_income.',
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
            'monthlyGross' => $this->money('monthly_gross'),
            'regime' => $this->inputString('regime', (string) $this->configValue('defaults.regime', 'new')),
            'age' => $this->inputInt('age', 30),
            'deductions' => is_array($deductions) ? $deductions : [],
            'monthsRemaining' => $this->inputInt('months_remaining', 12),
            'taxAlreadyDeducted' => $this->optionalMoney('tax_already_deducted'),
            'asOf' => $this->asOf(),
        ];
    }
}
