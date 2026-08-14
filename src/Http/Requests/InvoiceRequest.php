<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

final class InvoiceRequest extends PayrollFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:16', 'regex:/^[A-Za-z0-9\/\-]+$/'],
            'date' => ['sometimes', 'nullable', 'date'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:250'],
            'lines.*.hsn' => ['required', 'string', 'regex:/^\d{4,8}$/'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit' => ['sometimes', 'string', 'max:10'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'recipient' => ['required', 'array'],
            'recipient.name' => ['required', 'string', 'max:200'],
            'recipient.address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'recipient.gstin' => ['sometimes', 'nullable', 'string', 'regex:/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/i'],
            'recipient.state' => ['sometimes', 'nullable', 'string', 'max:80'],
            'recipient.state_code' => ['sometimes', 'nullable', 'string', 'max:2'],

            'supplier' => ['sometimes', 'array'],
            'supplier.gstin' => ['sometimes', 'nullable', 'string', 'regex:/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/i'],
            'supplier.state_code' => ['sometimes', 'nullable', 'string', 'max:2'],

            'place_of_supply' => ['sometimes', 'nullable', 'string', 'max:80'],
            'reverse_charge' => ['sometimes', 'boolean'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
            'number.max' => 'Rule 46(b) limits a tax invoice number to 16 characters, and allows only letters, '
                .'digits, hyphens and slashes. It must also be consecutive and unique within the financial year - '
                .'that sequence is yours to maintain, not something this package can invent for you.',
            'number.regex' => 'An invoice number may contain letters, digits, hyphens and slashes only, per rule 46(b).',
            'lines.min' => 'An invoice needs at least one line.',
            'lines.*.hsn.required' => 'Every line needs an HSN code for goods or a SAC for services. Rule 46 makes it '
                .'mandatory, and the number of digits depends on turnover: four for up to ₹5 crore, six above it.',
            'lines.*.hsn.regex' => 'An HSN or SAC is 4 to 8 digits.',
            'lines.*.gst_rate.required' => 'Every line carries its own GST rate. A single invoice routinely mixes '
                .'them - 18% on the service, 5% on the goods shipped with it - and the rate-wise summary at the foot '
                .'of the invoice is built by grouping them.',
            'lines.*.gst_rate.max' => 'A GST rate above 100% is not a rate. The rate is validated as a number rather '
                .'than against a fixed list of slabs on purpose: the slabs have been rationalised more than once, and '
                .'a hardcoded list would reject a lawful invoice the day after the next notification.',
            'recipient.gstin.regex' => 'A GSTIN is 15 characters: a two-digit state code, a ten-character PAN, an '
                .'entity digit, the letter Z, and a checksum character.',
            'supplier.gstin.regex' => 'A GSTIN is 15 characters: a two-digit state code, a ten-character PAN, an '
                .'entity digit, the letter Z, and a checksum character.',
            'place_of_supply.max' => 'The place of supply is a state name or its two-digit GST code. It decides '
                .'whether the tax splits into CGST and SGST or lands wholly as IGST, so it is worth getting right.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->input('lines', []);
        /** @var array<string, mixed> $recipient */
        $recipient = $this->input('recipient', []);
        /** @var array<string, mixed> $supplier */
        $supplier = $this->input('supplier', []);
        /** @var array<int, string> $notes */
        $notes = $this->input('notes', []);

        return [
            'number' => $this->inputString('number'),
            'lines' => is_array($lines) ? array_values($lines) : [],
            'recipient' => is_array($recipient) ? $recipient : [],
            'date' => $this->input('date') !== null ? $this->inputString('date') : null,
            'supplier' => is_array($supplier) ? $supplier : [],
            'placeOfSupply' => $this->input('place_of_supply') !== null ? $this->inputString('place_of_supply') : null,
            'reverseCharge' => $this->inputBool('reverse_charge', false),
            'terms' => $this->input('terms') !== null ? $this->inputString('terms') : null,
            'notes' => is_array($notes) ? array_values(array_map('strval', $notes)) : [],
        ];
    }

    public function wantsPdf(): bool
    {
        return $this->inputString('format', 'pdf') === 'pdf';
    }
}
