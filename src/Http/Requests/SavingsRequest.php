<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class SavingsRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_count' => ['required', 'integer', 'min:1', 'max:1000000'],
            'current_per_employee_per_month' => self::rupees(),
            'proposed_per_employee_per_month' => self::rupees(),
            'current_annual_platform_fee' => self::rupees(false),
            'proposed_annual_platform_fee' => self::rupees(false),
            'migration_cost' => self::rupees(false),
            'contract_months' => ['sometimes', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'current_per_employee_per_month.required' => 'What the current provider charges per employee per month. '
                .'Take it from the invoice rather than the price list - headline per-employee pricing rarely includes '
                .'the platform fee, which is why that is a separate field.',
            'contract_months.max' => 'Contract length is capped at 120 months. Projecting a saving a decade out says '
                .'more about the assumptions than the software.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'employeeCount' => $this->inputInt('employee_count'),
            'currentPerEmployeePerMonth' => $this->money('current_per_employee_per_month'),
            'proposedPerEmployeePerMonth' => $this->money('proposed_per_employee_per_month'),
            'currentAnnualPlatformFee' => $this->optionalMoney('current_annual_platform_fee'),
            'proposedAnnualPlatformFee' => $this->optionalMoney('proposed_annual_platform_fee'),
            'migrationCost' => $this->optionalMoney('migration_cost'),
            'contractMonths' => $this->inputInt('contract_months', 12),
        ];
    }
}
