<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class FnfRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monthly_gross' => self::rupees(),
            'monthly_basic' => array_merge(self::rupees(), ['lte:monthly_gross']),
            'joining_date' => ['required', 'date'],
            'last_working_date' => ['required', 'date', 'after_or_equal:joining_date'],
            'unpaid_days' => ['sometimes', 'integer', 'min:0', 'max:31'],
            'leave_days_balance' => ['sometimes', 'integer', 'min:0', 'max:2100'],
            'notice_period_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'notice_days_served' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'other_earnings' => self::rupees(false),
            'recoveries' => self::rupees(false),
            'separation_reason' => [
                'sometimes',
                'string',
                'in:resignation,retirement,superannuation,termination,retrenchment,death,disablement',
            ],
            'covered' => ['sometimes', 'boolean'],
            'state' => ['sometimes', 'string', 'max:80'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'monthly_basic.lte' => 'Monthly basic cannot exceed monthly gross - basic is a component of gross, not '
                .'an addition to it. Gratuity, leave encashment and notice pay are all computed on basic plus DA.',
            'last_working_date.after_or_equal' => 'The last working date cannot fall before the joining date.',
            'unpaid_days.max' => 'Loss-of-pay days apply to the final month only, so they cannot exceed 31.',
            'separation_reason.in' => 'The separation reason must be one of resignation, retirement, '
                .'superannuation, termination, retrenchment, death or disablement. Death and disablement waive the '
                .'five-year gratuity qualifying period under section 4(1).',
            'covered.boolean' => 'Set this false for an establishment outside the Payment of Gratuity Act. A covered '
                .'establishment divides by 26 working days; a non-covered one by 30 calendar days.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'monthlyGross' => $this->money('monthly_gross'),
            'monthlyBasic' => $this->money('monthly_basic'),
            'joiningDate' => new \DateTimeImmutable($this->inputString('joining_date')),
            'lastWorkingDate' => new \DateTimeImmutable($this->inputString('last_working_date')),
            'unpaidDays' => $this->inputInt('unpaid_days', 0),
            'leaveDaysBalance' => $this->inputInt('leave_days_balance', 0),
            'noticePeriodDays' => $this->inputInt('notice_period_days', 0),
            'noticeDaysServed' => $this->inputInt('notice_days_served', 0),
            'otherEarnings' => $this->optionalMoney('other_earnings'),
            'recoveries' => $this->optionalMoney('recoveries'),
            'separationReason' => $this->inputString('separation_reason', 'resignation'),
            'covered' => $this->inputBool('covered', true),
            'state' => $this->inputString('state', (string) $this->configValue('defaults.state', 'Karnataka')),
            'asOf' => $this->asOf(),
        ];
    }
}
