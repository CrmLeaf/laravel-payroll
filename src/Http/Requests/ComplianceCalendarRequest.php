<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class ComplianceCalendarRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'financial_year' => ['sometimes', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'only' => ['sometimes', 'array'],
            'only.*' => ['string', 'max:20'],
            'qrmp' => ['sometimes', 'boolean'],
            'as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'financial_year.regex' => 'The financial year must look like "2025-26". Indian financial years run '
                .'1 April to 31 March, so a single calendar year would be ambiguous.',
            'qrmp.boolean' => 'Set this when the taxpayer is on the Quarterly Return Monthly Payment scheme: GSTR-1 '
                .'and GSTR-3B then fall quarterly rather than monthly, with PMT-06 payments in between.',
            'only.array' => 'Pass "only" as a list of obligation codes, for example ["PF","ESI","TDS"].',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        /** @var array<int, string> $only */
        $only = $this->input('only', []);

        $financialYear = $this->inputString(
            'financial_year',
            (string) ($this->configValue('defaults.financial_year') ?? $this->currentFinancialYear()),
        );

        return [
            'financialYear' => $financialYear,
            'only' => is_array($only) ? array_values(array_map('strval', $only)) : [],
            'qrmp' => $this->inputBool('qrmp', false),
            'asOf' => is_string($this->input('as_of')) ? $this->inputString('as_of') : null,
        ];
    }

    private function currentFinancialYear(): string
    {
        $now = new \DateTimeImmutable('today');
        $startYear = (int) $now->format('n') >= 4 ? (int) $now->format('Y') : (int) $now->format('Y') - 1;

        return sprintf('%d-%02d', $startYear, ($startYear + 1) % 100);
    }
}
