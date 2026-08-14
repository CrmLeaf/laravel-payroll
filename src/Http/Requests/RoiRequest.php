<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class RoiRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_count' => ['required', 'integer', 'min:1', 'max:1000000'],
            'hourly_rate' => self::rupees(),
            'hours_per_cycle_manual' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'cycles_per_year' => ['sometimes', 'integer', 'min:1', 'max:53'],
            'software_annual_cost' => self::rupees(false),
            'automation_efficiency' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'penalty_cost_per_year' => self::rupees(false),
            'error_cost_per_year' => self::rupees(false),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'hourly_rate.required' => 'The fully loaded hourly cost of the person doing payroll today - salary plus '
                .'the employer\'s own PF, ESI and gratuity provision, not the take-home divided by hours.',
            'hours_per_cycle_manual.gt' => 'Hours per cycle must be above nought, otherwise there is nothing to save.',
            'automation_efficiency.max' => 'Automation efficiency is a share between 0 and 1. Claiming 1 asserts that '
                .'automation removes every manual hour, which no payroll run does - somebody still approves it.',
            'cycles_per_year.max' => 'Cycles per year cannot exceed 53. Monthly payroll is 12; weekly is 52.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'employeeCount' => $this->inputInt('employee_count'),
            'hourlyRate' => $this->money('hourly_rate'),
            'hoursPerCycleManual' => $this->inputFloat('hours_per_cycle_manual'),
            'cyclesPerYear' => $this->inputInt('cycles_per_year', 12),
            'softwareAnnualCost' => $this->optionalMoney('software_annual_cost'),
            'automationEfficiency' => $this->inputFloat('automation_efficiency', 0.8),
            'penaltyCostPerYear' => $this->optionalMoney('penalty_cost_per_year'),
            'errorCostPerYear' => $this->optionalMoney('error_cost_per_year'),
        ];
    }
}
