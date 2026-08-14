<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class LeaveEncashmentRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'average_monthly_salary' => self::rupees(),
            'leave_days_encashed' => ['required', 'integer', 'min:0', 'max:2100'],
            'years_of_service' => ['required', 'integer', 'min:0', 'max:70'],
            'government_employee' => ['sometimes', 'boolean'],
            'during_service' => ['sometimes', 'boolean'],
            'exemption_already_claimed' => self::rupees(false),
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'average_monthly_salary.required' => 'The average monthly salary is required. For section 10(10AA) it '
                .'means basic plus dearness allowance forming part of retirement benefits, averaged over the ten '
                .'months immediately before separation - not the final month\'s gross.',
            'leave_days_encashed.max' => 'That is more leave than a working life can accrue. The exemption is capped '
                .'at 30 days of leave for each completed year of service, so anything beyond that is taxable anyway.',
            'government_employee.boolean' => 'Central and state government employees have their leave encashment '
                .'wholly exempt on retirement; everyone else is limited by the four ceilings in section 10(10AA)(ii).',
            'during_service.boolean' => 'Leave encashed while still in service is fully taxable - the exemption '
                .'applies only to encashment at retirement or resignation.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'averageMonthlySalary' => $this->money('average_monthly_salary'),
            'leaveDaysEncashed' => $this->inputInt('leave_days_encashed'),
            'yearsOfService' => $this->inputInt('years_of_service'),
            'governmentEmployee' => $this->inputBool('government_employee', false),
            'duringService' => $this->inputBool('during_service', false),
            'exemptionAlreadyClaimed' => $this->optionalMoney('exemption_already_claimed'),
            'asOf' => $this->asOf(),
        ];
    }
}
