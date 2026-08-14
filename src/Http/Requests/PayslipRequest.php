<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class PayslipRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee' => ['required', 'array'],
            'employee.name' => ['required', 'string', 'max:120'],
            'employee.code' => ['sometimes', 'nullable', 'string', 'max:40'],
            'employee.designation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'employee.department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'employee.pan' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{5}\d{4}[A-Z]$/i'],
            'employee.uan' => ['sometimes', 'nullable', 'digits:12'],
            'employee.esic_number' => ['sometimes', 'nullable', 'digits:17'],
            'employee.bank_account' => ['sometimes', 'nullable', 'string', 'max:34'],
            'employee.ifsc' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],

            'month' => ['required', 'string', 'max:30'],

            'earnings' => ['required', 'array', 'min:1'],
            'earnings.*' => ['numeric', 'min:0'],
            'deductions' => ['sometimes', 'array'],
            'deductions.*' => ['numeric', 'min:0'],

            'attendance' => ['sometimes', 'array'],
            'attendance.working_days' => ['sometimes', 'numeric', 'min:0', 'max:31'],
            'attendance.paid_days' => ['sometimes', 'numeric', 'min:0', 'max:31'],
            'attendance.lop_days' => ['sometimes', 'numeric', 'min:0', 'max:31'],

            'notes' => ['sometimes', 'array'],
            'notes.*' => ['string', 'max:500'],

            'format' => ['sometimes', 'string', 'in:pdf,html'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'earnings.min' => 'A payslip needs at least one earning head. Rule 26 of the Payment of Wages (Central) '
                .'Rules requires the wage period, the wages earned and every deduction with its head shown '
                .'separately - a single "net pay" figure does not satisfy it.',
            'employee.uan.digits' => 'A Universal Account Number is exactly 12 digits.',
            'employee.esic_number.digits' => 'An ESIC insurance number is exactly 17 digits.',
            'employee.pan.regex' => 'A PAN is five letters, four digits and a letter, for example ABCDE1234F.',
            'employee.ifsc.regex' => 'An IFSC is four letters, a zero, and six alphanumerics, for example HDFC0001234.',
            'deductions.*.min' => 'A deduction cannot be negative. To pay something back to the employee, add it as '
                .'an earning head instead - the payslip has to show each deduction under its own head.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        /** @var array<string, mixed> $employee */
        $employee = $this->input('employee', []);
        /** @var array<string, mixed> $earnings */
        $earnings = $this->input('earnings', []);
        /** @var array<string, mixed> $deductions */
        $deductions = $this->input('deductions', []);
        /** @var array<string, mixed> $attendance */
        $attendance = $this->input('attendance', []);
        /** @var array<int, string> $notes */
        $notes = $this->input('notes', []);

        return [
            'employee' => is_array($employee) ? $employee : [],
            'month' => $this->inputString('month'),
            'earnings' => is_array($earnings) ? $earnings : [],
            'deductions' => is_array($deductions) ? $deductions : [],
            'attendance' => is_array($attendance) ? $attendance : [],
            'notes' => is_array($notes) ? array_values(array_map('strval', $notes)) : [],
        ];
    }

    public function wantsPdf(): bool
    {
        return $this->inputString('format', 'pdf') === 'pdf';
    }
}
