<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class EsiRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gross_wages' => self::rupees(),
            'disabled' => ['sometimes', 'boolean'],
            'average_daily_wage' => self::rupees(false),
            'continuing_from_prior_period' => ['sometimes', 'boolean'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'gross_wages.required' => 'Gross wages are required. ESI is charged on gross wages as defined in '
                .'section 2(22) - basic, all allowances and overtime - not on basic alone.',
            'continuing_from_prior_period.boolean' => 'Set this when the employee was already contributing at the '
                .'start of the contribution period. Under regulation 4, crossing the wage limit mid-period does not '
                .'stop contributions: they continue to the end of that period.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'grossWages' => $this->money('gross_wages'),
            'disabled' => $this->inputBool('disabled', false),
            'averageDailyWage' => $this->optionalMoney('average_daily_wage'),
            'continuingFromPriorPeriod' => $this->inputBool('continuing_from_prior_period', false),
            'asOf' => $this->asOf(),
        ];
    }
}
