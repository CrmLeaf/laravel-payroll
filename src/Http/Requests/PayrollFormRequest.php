<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Requests;

use Crmleaf\Payroll\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared plumbing for the tool requests: rupee fields, the as-of date, and the
 * config-backed defaults.
 *
 * Authorisation is left wide open here because the package cannot know your
 * policy, and pretending otherwise would be worse than saying so. The routes
 * are off by default and carry whatever middleware you configure; that is the
 * gate. If you enable them, put your guard in `payroll.routes.middleware`.
 */
abstract class PayrollFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A required rupee amount, as Money.
     */
    protected function money(string $key, Money|int|float|null $default = null): Money
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return $default instanceof Money ? $default : Money::fromRupees($default ?? 0);
        }

        return Money::fromRupees(is_string($value) ? (float) $value : $value);
    }

    /**
     * An optional rupee amount. Returns null rather than zero when absent,
     * because several calculators treat "no figure given" differently from
     * "a figure of nought" - an unstated minimum wage is not a zero one.
     */
    protected function optionalMoney(string $key): ?Money
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : Money::fromRupees(is_string($value) ? (float) $value : $value);
    }

    /**
     * The date whose statutory rates apply. Everything downstream is versioned
     * by this, which is what makes recomputing a closed period possible.
     */
    protected function asOf(): ?string
    {
        $value = $this->input('as_of') ?? $this->input('financial_year') ?? $this->configValue('defaults.financial_year');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * These are deliberately not called boolean()/integer()/string(): those
     * names already exist on Illuminate's request and have their own defaulting
     * behaviour, and quietly overriding them is how a package breaks somebody
     * else's middleware. The `input` prefix keeps ours out of the way.
     */
    protected function inputBool(string $key, bool $default = false): bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    protected function inputInt(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    protected function inputFloat(string $key, float $default = 0.0): float
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? $default : (float) $value;
    }

    protected function inputString(string $key, string $default = ''): string
    {
        $value = $this->input($key);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
    }

    protected function configValue(string $key, mixed $default = null): mixed
    {
        return config('payroll.'.$key, $default);
    }

    /**
     * Rules for a rupee field, kept in one place so "negative salary" reads the
     * same in sixteen requests.
     *
     * @return array<int, string>
     */
    protected static function rupees(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'numeric',
            'min:0',
            'max:100000000000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numeric' => ':attribute must be a number in rupees, without commas or a currency symbol.',
            'min' => ':attribute cannot be negative - payroll amounts are always nought or more.',
            'as_of.date' => 'The as-of date decides which statutory rates apply, so it must be a real date '
                .'(for example 2024-03-31 to recompute against the rates in force that day).',
        ];
    }
}
