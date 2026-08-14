<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class BonusRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monthly_wages' => self::rupees(),
            'bonus_rate' => ['sometimes', 'numeric', 'min:8.33', 'max:20'],
            'months_worked' => ['sometimes', 'integer', 'min:0', 'max:12'],
            'days_worked' => ['sometimes', 'integer', 'min:0', 'max:31'],
            'minimum_wage' => self::rupees(false),
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'bonus_rate.min' => 'The bonus rate cannot fall below 8.33%. Section 10 of the Payment of Bonus Act 1965 '
                .'makes that a minimum payable even in a year the establishment made no allocable surplus - hence '
                .'the name "minimum bonus".',
            'bonus_rate.max' => 'The bonus rate cannot exceed 20%. Section 11 caps the maximum bonus at 20% of '
                .'salary or wage however large the allocable surplus is; anything paid above that is ex gratia, not '
                .'statutory bonus.',
            'months_worked.min' => 'Months worked cannot be negative. Section 8 requires 30 working days in the '
                .'accounting year before any bonus is payable, so a short stint gives a nil result rather than an error.',
            'days_worked.max' => 'Days worked in the final month cannot exceed 31.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'monthlyWages' => $this->money('monthly_wages'),
            'bonusRate' => $this->inputFloat('bonus_rate', 8.33),
            'monthsWorked' => $this->inputInt('months_worked', 12),
            'daysWorked' => $this->inputInt('days_worked', 30),
            'minimumWage' => $this->optionalMoney('minimum_wage'),
            'asOf' => $this->asOf(),
        ];
    }
}
