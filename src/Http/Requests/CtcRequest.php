<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class CtcRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'annual_ctc' => self::rupees(),
            'state' => ['sometimes', 'string', 'max:80'],
            'basic_percent' => ['sometimes', 'numeric', 'gt:0', 'max:100'],
            'hra_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'include_bonus' => ['sometimes', 'boolean'],
            'include_leave_encashment' => ['sometimes', 'boolean'],
            'employer_pf_restricted_to_ceiling' => ['sometimes', 'boolean'],
            'regime' => ['sometimes', 'string', 'in:new,old'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'annual_ctc.required' => 'Annual CTC is required, and it is the annual figure - the whole cost to the '
                .'company including the employer\'s PF, ESI and gratuity provision, not the offered gross.',
            'basic_percent.gt' => 'Basic must be more than 0% of CTC. It is the base for PF, gratuity and leave '
                .'encashment, so a structure with no basic is not a structure.',
            'basic_percent.max' => 'Basic cannot exceed 100% of CTC. The Code on Wages pushes basic towards 50%; '
                .'40% is the common private-sector figure.',
            'hra_percent.max' => 'HRA is expressed as a percentage of basic and cannot exceed 100%. The section '
                .'10(13A) exemption tops out at 50% of basic in the four metros and 40% elsewhere.',
            'state.max' => 'The state name decides which professional tax schedule applies.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'annualCtc' => $this->money('annual_ctc'),
            'state' => $this->inputString('state', (string) $this->configValue('defaults.state', 'Karnataka')),
            'basicPercent' => $this->inputFloat('basic_percent', (float) $this->configValue('defaults.basic_percent', 40)),
            'hraPercent' => $this->inputFloat('hra_percent', (float) $this->configValue('defaults.hra_percent', 50)),
            'includeBonus' => $this->inputBool('include_bonus', false),
            'includeLeaveEncashment' => $this->inputBool('include_leave_encashment', false),
            'employerPfRestrictedToCeiling' => $this->inputBool('employer_pf_restricted_to_ceiling', true),
            'regime' => $this->inputString('regime', (string) $this->configValue('defaults.regime', 'new')),
            'asOf' => $this->asOf(),
        ];
    }
}
