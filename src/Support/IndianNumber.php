<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Support;

use Crmleaf\Payroll\Money;

/**
 * Indian numbering for documents: the lakh/crore grouping and the amount in
 * words that a tax invoice is expected to carry.
 *
 * PHP's NumberFormatter can do the words in en_IN, but it is an ext-intl
 * dependency for one string on one document, and it renders "lakh" as
 * "hundred thousand" in several ICU versions. Doing it by hand is thirty lines
 * and always says what an Indian invoice is supposed to say.
 */
final class IndianNumber
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
        'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * Group with the Indian comma pattern: 12,34,567 rather than 1,234,567.
     */
    public static function group(float|int $value, int $decimals = 2): string
    {
        $negative = $value < 0;
        $value = abs($value);

        $whole = (string) (int) $value;
        $fraction = $decimals > 0
            ? substr(number_format($value - (int) $value, $decimals, '.', ''), 1)
            : '';

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = (string) preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest.','.$last3;
        }

        return ($negative ? '-' : '').$whole.$fraction;
    }

    /**
     * "Rupees Twelve Lakh Thirty Four Thousand Five Hundred Sixty Seven and
     * Fifty Paise Only" - the form invoices use.
     */
    public static function toWords(Money|float|int $amount, string $currency = 'Rupees'): string
    {
        $paise = $amount instanceof Money ? $amount->paise : (int) round(((float) $amount) * 100);
        $negative = $paise < 0;
        $paise = abs($paise);

        $rupees = intdiv($paise, 100);
        $fraction = $paise % 100;

        $words = trim($currency.' '.self::words($rupees));

        if ($fraction > 0) {
            $words .= ' and '.self::words($fraction).' Paise';
        }

        return ($negative ? 'Minus ' : '').$words.' Only';
    }

    private static function words(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $parts = [];

        // Indian units are not powers of a thousand past the first: after
        // thousand comes lakh (10^5) and then crore (10^7), so the divisors go
        // 100, 100, 1000 rather than 1000, 1000, 1000.
        foreach ([10_000_000 => 'Crore', 100_000 => 'Lakh', 1_000 => 'Thousand', 100 => 'Hundred'] as $divisor => $label) {
            if ($number >= $divisor) {
                $parts[] = self::words(intdiv($number, $divisor)).' '.$label;
                $number %= $divisor;
            }
        }

        if ($number > 0) {
            $parts[] = $number < 20
                ? self::ONES[$number]
                : trim(self::TENS[intdiv($number, 10)].' '.self::ONES[$number % 10]);
        }

        return implode(' ', $parts);
    }
}
