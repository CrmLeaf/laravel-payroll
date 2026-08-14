<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Exceptions\InvalidInputException;
use Crmleaf\Payroll\Laravel\Support\IndianNumber;
use Crmleaf\Payroll\Money;

/**
 * A GST tax invoice, rendered in your application against your config.
 *
 * The one thing this gets right that hand-rolled invoice templates routinely
 * get wrong is the CGST/SGST versus IGST decision. It is not a preference and
 * not a per-customer setting: it follows from the place of supply. If the
 * supplier's state and the place of supply are the same state, the tax splits
 * into central and state halves; if they differ, the whole of it is integrated
 * tax. Charging CGST+SGST on an inter-state supply files money with a state
 * that is not entitled to it and leaves the recipient unable to claim credit.
 */
final class InvoiceGenerator extends DocumentGenerator
{
    protected function view(): string
    {
        return 'payroll::documents.invoice';
    }

    /**
     * @param array<int, array<string, mixed>> $lines each with description, hsn (or sac),
     *                                                quantity, unit_price, gst_rate and
     *                                                optionally unit and discount
     * @param array<string, mixed> $recipient name, address, gstin, state, state_code
     * @param array<string, mixed> $supplier overrides the payroll.company config for this invoice
     * @param string|null $placeOfSupply state name or two-digit code; defaults to the
     *                                   recipient's own state, which is the common case
     * @param array<int, string> $notes free-text notes printed under the totals
     */
    public function generate(
        string $number,
        array $lines,
        array $recipient,
        \DateTimeImmutable|string|null $date = null,
        array $supplier = [],
        ?string $placeOfSupply = null,
        bool $reverseCharge = false,
        ?string $terms = null,
        array $notes = [],
        ?string $currency = 'INR',
    ): Document {
        if ($lines === []) {
            throw InvalidInputException::outOfRange('Invoice lines', 'at least one line', 0);
        }

        $date = $this->date($date);
        $supplier = $this->company($supplier);

        $supplierCode = GstStateCodes::resolve(
            isset($supplier['gstin']) ? (string) $supplier['gstin'] : null,
            isset($supplier['state_code']) ? (string) $supplier['state_code'] : null,
            isset($supplier['state']) ? (string) $supplier['state'] : null,
        );

        $placeOfSupplyCode = GstStateCodes::resolve(null, $placeOfSupply)
            ?? GstStateCodes::resolve(
                isset($recipient['gstin']) ? (string) $recipient['gstin'] : null,
                isset($recipient['state_code']) ? (string) $recipient['state_code'] : null,
                isset($recipient['state']) ? (string) $recipient['state'] : null,
            );

        $taxable = $this->anyLineIsTaxed($lines);

        if ($taxable && $supplierCode === null) {
            throw new InvalidInputException(
                'Cannot decide between CGST+SGST and IGST without the supplier\'s state. Set '
                .'payroll.company.gstin (its first two digits are the state code) or '
                .'payroll.company.state_code, or pass supplier: [\'state_code\' => \'29\'].',
            );
        }

        // An unregistered walk-in customer often has no state on file at all.
        // Treating the supply as intra-state is the correct default there -
        // place of supply for a B2C over-the-counter supply is the supplier's
        // location - but it is an assumption, so it goes on the record.
        $assumedPlaceOfSupply = $placeOfSupplyCode === null;
        $placeOfSupplyCode ??= $supplierCode;

        $interState = $supplierCode !== null
            && $placeOfSupplyCode !== null
            && $supplierCode !== $placeOfSupplyCode;

        $invoiceLines = [];

        foreach ($lines as $line) {
            if (!is_array($line)) {
                throw InvalidInputException::outOfRange('Invoice line', 'an array', $line);
            }

            $invoiceLines[] = InvoiceLine::fromArray($line, $interState);
        }

        $totals = $this->totals($invoiceLines);
        $summary = $this->hsnSummary($invoiceLines, $interState);

        $data = [
            'number' => $number,
            'date' => $date,
            'currency' => $currency ?? 'INR',
            'supplier' => $supplier,
            'supplier_state_code' => $supplierCode,
            'supplier_state' => GstStateCodes::name($supplierCode),
            'recipient' => $recipient,
            'place_of_supply_code' => $placeOfSupplyCode,
            'place_of_supply' => GstStateCodes::name($placeOfSupplyCode),
            'place_of_supply_assumed' => $assumedPlaceOfSupply,
            'inter_state' => $interState,
            'tax_type' => $interState ? 'IGST' : 'CGST + SGST',
            'reverse_charge' => $reverseCharge,
            'lines' => $invoiceLines,
            'hsn_summary' => $summary,
            'terms' => $terms,
            'notes' => array_values(array_filter($notes)),
            'amount_in_words' => IndianNumber::toWords($totals['grand_total']),
        ] + $totals;

        return $this->document($data, sprintf('invoice-%s.pdf', self::slug($number)));
    }

    /**
     * The invoice totals, including the round-off line.
     *
     * GST is computed to the paise and then the payable is rounded to the
     * nearest rupee under section 170 of the CGST Act. The difference is shown
     * rather than absorbed, because the recipient's books have to reconcile
     * the tax they claim credit for against the tax we charged, and a silently
     * dropped 40 paise breaks that reconciliation.
     *
     * @param array<int, InvoiceLine> $lines
     *
     * @return array<string, mixed>
     */
    private function totals(array $lines): array
    {
        $taxable = Money::zero();
        $discount = Money::zero();
        $gross = Money::zero();
        $cgst = Money::zero();
        $sgst = Money::zero();
        $igst = Money::zero();

        foreach ($lines as $line) {
            $gross = $gross->add($line->grossValue);
            $discount = $discount->add($line->discount);
            $taxable = $taxable->add($line->taxableValue);
            $cgst = $cgst->add($line->cgst);
            $sgst = $sgst->add($line->sgst);
            $igst = $igst->add($line->igst);
        }

        $totalTax = $cgst->add($sgst, $igst);
        $payable = $taxable->add($totalTax);
        $rounded = $payable->roundToRupee();

        return [
            'gross_value' => $gross,
            'total_discount' => $discount,
            'taxable_value' => $taxable,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total_tax' => $totalTax,
            'total_before_rounding' => $payable,
            'round_off' => $rounded->subtract($payable),
            'grand_total' => $rounded,
        ];
    }

    /**
     * The rate-wise HSN/SAC summary rule 46 asks for, keyed by HSN and rate so
     * two lines of the same goods at the same rate collapse into one row.
     *
     * @param array<int, InvoiceLine> $lines
     *
     * @return array<int, array<string, mixed>>
     */
    private function hsnSummary(array $lines, bool $interState): array
    {
        $rows = [];

        foreach ($lines as $line) {
            $key = $line->hsn.'@'.$line->gstRate;

            $rows[$key] ??= [
                'hsn' => $line->hsn,
                'gst_rate' => $line->gstRate,
                'quantity' => 0.0,
                'taxable_value' => Money::zero(),
                'cgst' => Money::zero(),
                'sgst' => Money::zero(),
                'igst' => Money::zero(),
                'total_tax' => Money::zero(),
            ];

            $rows[$key]['quantity'] += $line->quantity;
            $rows[$key]['taxable_value'] = $rows[$key]['taxable_value']->add($line->taxableValue);
            $rows[$key]['cgst'] = $rows[$key]['cgst']->add($line->cgst);
            $rows[$key]['sgst'] = $rows[$key]['sgst']->add($line->sgst);
            $rows[$key]['igst'] = $rows[$key]['igst']->add($line->igst);
            $rows[$key]['total_tax'] = $rows[$key]['total_tax']->add($line->totalTax);
            $rows[$key]['inter_state'] = $interState;
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * @param array<int, mixed> $lines
     */
    private function anyLineIsTaxed(array $lines): bool
    {
        foreach ($lines as $line) {
            if (is_array($line) && (float) ($line['gst_rate'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function date(\DateTimeImmutable|string|null $date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return new \DateTimeImmutable($date ?? 'today');
    }
}
