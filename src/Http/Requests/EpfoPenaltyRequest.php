<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class EpfoPenaltyRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contribution_amount' => self::rupees(),
            'wage_month' => ['required', 'date'],
            'actual_payment_date' => ['required', 'date', 'after_or_equal:wage_month'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'contribution_amount.required' => 'The arrears amount is required, and it means the whole remittance in '
                .'default - EPF, EPS and EDLI together, as it appears on the challan. Section 7Q interest and '
                .'section 14B damages are both charged on the amount due, not on the employee share alone.',
            'wage_month.required' => 'The wage month is required. The due date runs from the month the wages relate '
                .'to, not the month they were paid: contributions for April fall due by 15 May.',
            'actual_payment_date.after_or_equal' => 'The payment date cannot precede the wage month it settles.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return [
            'contributionAmount' => $this->money('contribution_amount'),
            'wageMonth' => new \DateTimeImmutable($this->inputString('wage_month')),
            'actualPaymentDate' => new \DateTimeImmutable($this->inputString('actual_payment_date')),
            'asOf' => $this->asOf(),
        ];
    }
}
