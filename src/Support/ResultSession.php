<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Support;

use Crmleaf\Payroll\Contracts\CalculationResult;

/**
 * The handover between the controller and the Blade component when JavaScript
 * is not available: the controller flashes a result, the redirect lands back on
 * the embedding page, and the component picks its own result up again.
 *
 * A class rather than a constant on the controller trait, because a trait
 * constant cannot be read through the trait name - and the component has no
 * business reaching into a controller for it either way.
 */
final class ResultSession
{
    public const KEY = 'payroll.result';

    /**
     * @return array{tool: string, result: array<string, mixed>}
     */
    public static function payload(string $tool, CalculationResult $result): array
    {
        return ['tool' => $tool, 'result' => $result->jsonSerialize()];
    }

    /**
     * The flashed result, but only if it belongs to the tool asking for it.
     * Two components on one page would otherwise both render the answer to
     * whichever form was submitted.
     *
     * @return array<string, mixed>|null
     */
    public static function read(string $tool): ?array
    {
        /** @var mixed $flashed */
        $flashed = session(self::KEY);

        if (!is_array($flashed) || ($flashed['tool'] ?? null) !== $tool) {
            return null;
        }

        $result = $flashed['result'] ?? null;

        return is_array($result) ? $result : null;
    }
}
