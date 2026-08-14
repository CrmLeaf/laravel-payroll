<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Exceptions\InvalidInputException;

/**
 * The two-digit GST state codes, and the one question an invoice needs them
 * for: is this supply intra-state or inter-state?
 *
 * Nothing else about an invoice hinges on geography, but this does, and it
 * decides whether the tax splits into CGST plus SGST or lands wholly as IGST.
 * Getting it wrong is not a rounding error - it files the tax with the wrong
 * government.
 */
final class GstStateCodes
{
    private const CODES = [
        '01' => 'Jammu and Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Odisha',
        '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '25' => 'Daman and Diu',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu',
        '27' => 'Maharashtra',
        '28' => 'Andhra Pradesh (before bifurcation)',
        '29' => 'Karnataka',
        '30' => 'Goa',
        '31' => 'Lakshadweep',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
        '38' => 'Ladakh',
        '97' => 'Other Territory',
        '99' => 'Centre Jurisdiction',
    ];

    /**
     * Resolve a state code from whatever the caller had to hand: an explicit
     * code, a GSTIN, or a state name.
     *
     * A GSTIN wins over an explicit code when both are given, because the
     * GSTIN is the registration the return is filed under - if the two
     * disagree, the typo is in the loose code, not in the fifteen-character
     * number somebody copied off a certificate.
     */
    public static function resolve(?string $gstin, ?string $code = null, ?string $stateName = null): ?string
    {
        if (is_string($gstin) && preg_match('/^\d{2}/', trim($gstin), $matches) === 1) {
            return $matches[0];
        }

        // Accept a code or a state name in the same argument. Callers get this
        // value from a form field labelled "place of supply", and people type
        // "Karnataka" there at least as often as they type "29".
        if (is_string($code) && trim($code) !== '') {
            $digits = (string) preg_replace('/\D/', '', $code);

            if ($digits !== '') {
                $normalised = str_pad($digits, 2, '0', STR_PAD_LEFT);

                if (isset(self::CODES[$normalised])) {
                    return $normalised;
                }

                throw InvalidInputException::unknownOption(
                    'GST state code',
                    $code,
                    array_map(strval(...), array_keys(self::CODES)),
                );
            }

            $byName = self::codeForName($code);

            if ($byName !== null) {
                return $byName;
            }

            throw InvalidInputException::unknownOption('state', $code, array_values(self::CODES));
        }

        if (is_string($stateName) && trim($stateName) !== '') {
            return self::codeForName($stateName);
        }

        return null;
    }

    public static function name(?string $code): ?string
    {
        return $code === null ? null : (self::CODES[$code] ?? null);
    }

    public static function codeForName(string $stateName): ?string
    {
        $needle = self::normalise($stateName);

        foreach (self::CODES as $code => $name) {
            if (self::normalise($name) === $needle) {
                // PHP silently casts numeric array keys to int, so '29' comes
                // back as 29 and '07' stays a string. Re-padding restores the
                // two-digit form the GSTIN and the invoice both use.
                return str_pad((string) $code, 2, '0', STR_PAD_LEFT);
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $codes = [];

        foreach (self::CODES as $code => $name) {
            $codes[str_pad((string) $code, 2, '0', STR_PAD_LEFT)] = $name;
        }

        return $codes;
    }

    public static function isValid(string $code): bool
    {
        return isset(self::CODES[$code]);
    }

    /**
     * A GSTIN is 15 characters: two-digit state code, ten-character PAN, an
     * entity digit, a fixed 'Z', and a checksum character. We validate shape
     * only - the checksum is the portal's job, and rejecting a valid GSTIN
     * because our checksum implementation drifted would be worse than useless.
     */
    public static function looksLikeGstin(string $gstin): bool
    {
        return preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/', strtoupper(trim($gstin))) === 1;
    }

    private static function normalise(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z]/i', '', $value));
    }
}
