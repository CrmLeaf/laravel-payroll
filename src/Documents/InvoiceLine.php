<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Exceptions\InvalidInputException;
use Crmleaf\Payroll\Money;

/**
 * One line of a tax invoice, with its own HSN/SAC and GST rate.
 *
 * The rate belongs to the line rather than the invoice because a single
 * invoice routinely carries several: 18% on the software subscription, 5% on
 * the printed manual shipped with it. Rule 46 wants the HSN or SAC against
 * each of them, and the rate-wise summary at the foot of the invoice is built
 * by grouping these, not by assuming one rate throughout.
 */
final class InvoiceLine
{
    public readonly Money $grossValue;
    public readonly Money $discount;
    public readonly Money $taxableValue;
    public readonly Money $cgst;
    public readonly Money $sgst;
    public readonly Money $igst;
    public readonly Money $totalTax;
    public readonly Money $total;

    public function __construct(
        public readonly string $description,
        public readonly string $hsn,
        public readonly float $quantity,
        public readonly Money $unitPrice,
        public readonly float $gstRate,
        public readonly string $unit = 'NOS',
        Money|int|float|null $discountAmount = null,
        public readonly bool $interState = false,
    ) {
        if ($quantity < 0) {
            throw InvalidInputException::negative('Line quantity');
        }

        if ($unitPrice->isNegative()) {
            throw InvalidInputException::negative('Unit price');
        }

        if ($gstRate < 0 || $gstRate > 100) {
            throw InvalidInputException::outOfRange('GST rate', 'between 0 and 100', $gstRate);
        }

        $this->grossValue = $unitPrice->multiply($quantity);

        $this->discount = match (true) {
            $discountAmount instanceof Money => $discountAmount,
            $discountAmount === null => Money::zero(),
            default => Money::fromRupees($discountAmount),
        };

        if ($this->discount->greaterThan($this->grossValue)) {
            throw InvalidInputException::outOfRange(
                'Line discount',
                'no more than the line value of '.$this->grossValue->format(),
                $this->discount->toRupees(),
            );
        }

        $this->taxableValue = $this->grossValue->subtract($this->discount);

        // Intra-state supply splits the rate down the middle: an 18% supply is
        // 9% CGST to the Centre and 9% SGST to the state. Inter-state is not a
        // different amount of tax, only a different destination - the whole
        // 18% goes to IGST, which the Centre later apportions.
        if ($interState) {
            $this->igst = $this->taxableValue->percentage($gstRate);
            $this->cgst = Money::zero();
            $this->sgst = Money::zero();
        } else {
            $this->igst = Money::zero();
            $this->cgst = $this->taxableValue->percentage($gstRate / 2);
            // Derived by subtraction rather than computed twice, so an odd
            // paise on a half-rate lands somewhere instead of vanishing.
            $this->sgst = $this->taxableValue->percentage($gstRate)->subtract($this->cgst);
        }

        $this->totalTax = $this->cgst->add($this->sgst, $this->igst);
        $this->total = $this->taxableValue->add($this->totalTax);
    }

    /**
     * @param array<string, mixed> $line
     */
    public static function fromArray(array $line, bool $interState): self
    {
        return new self(
            description: (string) ($line['description'] ?? ''),
            hsn: (string) ($line['hsn'] ?? $line['sac'] ?? ''),
            quantity: (float) ($line['quantity'] ?? 1),
            unitPrice: self::money($line['unit_price'] ?? $line['rate'] ?? 0),
            gstRate: (float) ($line['gst_rate'] ?? 0),
            unit: (string) ($line['unit'] ?? 'NOS'),
            discountAmount: isset($line['discount']) ? self::money($line['discount']) : null,
            interState: $interState,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'hsn' => $this->hsn,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->toRupees(),
            'unit_price_formatted' => $this->unitPrice->format(),
            'gross_value' => $this->grossValue->toRupees(),
            'gross_value_formatted' => $this->grossValue->format(),
            'discount' => $this->discount->toRupees(),
            'discount_formatted' => $this->discount->format(),
            'taxable_value' => $this->taxableValue->toRupees(),
            'taxable_value_formatted' => $this->taxableValue->format(),
            'gst_rate' => $this->gstRate,
            'cgst' => $this->cgst->toRupees(),
            'cgst_formatted' => $this->cgst->format(),
            'sgst' => $this->sgst->toRupees(),
            'sgst_formatted' => $this->sgst->format(),
            'igst' => $this->igst->toRupees(),
            'igst_formatted' => $this->igst->format(),
            'total_tax' => $this->totalTax->toRupees(),
            'total_tax_formatted' => $this->totalTax->format(),
            'total' => $this->total->toRupees(),
            'total_formatted' => $this->total->format(),
        ];
    }

    private static function money(mixed $value): Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return Money::fromRupees($value);
        }

        throw InvalidInputException::outOfRange('Invoice amount', 'numeric', $value);
    }
}
