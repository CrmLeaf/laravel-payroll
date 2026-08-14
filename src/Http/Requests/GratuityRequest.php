<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class GratuityRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'last_drawn_salary' => self::rupees(),
            'years_of_service' => ['required', 'integer', 'min:0', 'max:70'],
            'months_of_service' => ['sometimes', 'integer', 'min:0', 'max:11'],
            'covered' => ['sometimes', 'boolean'],
            'separation_reason' => [
                'sometimes',
                'string',
                'in:resignation,retirement,superannuation,termination,retrenchment,death,disablement',
            ],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'last_drawn_salary.required' => 'Last drawn salary is required, and it means basic plus dearness '
                .'allowance only - section 2(s) of the Payment of Gratuity Act excludes HRA, bonus, overtime and '
                .'other allowances from "wages".',
            'months_of_service.max' => 'Months of service must be 0 to 11; whole years belong in years_of_service. '
                .'A part-year above six months rounds the service up by a year, which is why the two are separate.',
            'separation_reason.in' => 'The separation reason must be one of resignation, retirement, '
                .'superannuation, termination, retrenchment, death or disablement. It is not cosmetic: section 4(1) '
                .'waives the five-year qualifying period where employment ends by death or disablement.',
            'years_of_service.max' => 'Years of service must be 70 or below.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'lastDrawnSalary' => $this->money('last_drawn_salary'),
            'yearsOfService' => $this->inputInt('years_of_service'),
            'monthsOfService' => $this->inputInt('months_of_service', 0),
            'covered' => $this->inputBool('covered', true),
            'separationReason' => $this->inputString('separation_reason', 'resignation'),
            'asOf' => $this->asOf(),
        ];
    }
}
