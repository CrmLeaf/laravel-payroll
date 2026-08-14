<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

use Crmleaf\Payroll\Laravel\Support\ResultSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

/**
 * Base for the calculator components.
 *
 * The components are built to work with JavaScript switched off, which is not
 * nostalgia: these get embedded on marketing pages, in intranets behind
 * content-security policies, and in emails-turned-pages, and a calculator that
 * renders as an inert form in any of those is worse than no calculator. So the
 * form posts to a real route, the route redirects back with the result in the
 * session, and the component renders it. The @crmleaf/payroll-js bundle, when
 * present, intercepts the submit and does the same arithmetic in the browser -
 * an enhancement over a working baseline rather than the only path.
 */
abstract class PayrollComponent extends Component
{
    /** The tool slug: the route name suffix and the session discriminator. */
    public const TOOL = '';

    /** @var array<string, mixed>|null */
    public readonly ?array $result;

    public readonly string $tool;
    public readonly string $heading;
    public readonly ?string $action;
    public readonly bool $enhance;
    public readonly bool $styles;
    public readonly string $id;

    /**
     * @param array<string, mixed>|null $result a result to render instead of looking in the session,
     *                                          e.g. one you calculated in your own controller
     * @param string|null $action override the form target; useful when you proxy the
     *                            calculation through a route of your own
     */
    public function __construct(
        ?array $result = null,
        ?string $action = null,
        ?string $heading = null,
    ) {
        $this->tool = static::TOOL;
        $this->heading = $heading ?? $this->defaultHeading();
        $this->result = $result ?? ResultSession::read(static::TOOL);
        $this->action = $action ?? $this->routeUrl();
        $this->enhance = (bool) config('payroll.components.progressive_enhancement', true);
        $this->styles = (bool) config('payroll.components.styles', true);
        $this->id = 'payroll-'.static::TOOL.'-'.substr(md5(static::class.spl_object_id($this)), 0, 8);
    }

    /**
     * The form fields, in display order.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract public function fields(): array;

    /**
     * Headline figures pulled out of the result, as label => result array key.
     * Everything else the result carries is shown in the working below them.
     *
     * @return array<string, string>
     */
    abstract public function headline(): array;

    abstract protected function defaultHeading(): string;

    public function render(): View
    {
        return view('payroll::components.'.$this->viewName());
    }

    /**
     * A headline row's value, formatted. Money keys in every result carry a
     * matching `*_formatted` sibling, so prefer that and fall back to the raw
     * value for counts, rates and flags.
     */
    public function value(string $key): string
    {
        if ($this->result === null) {
            return '';
        }

        $formatted = $this->result[$key.'_formatted'] ?? null;

        if (is_scalar($formatted)) {
            return (string) $formatted;
        }

        $value = $this->result[$key] ?? null;

        return match (true) {
            is_bool($value) => $value ? 'Yes' : 'No',
            is_float($value) => rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.'),
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(): array
    {
        $steps = $this->result['steps'] ?? [];

        return is_array($steps) ? $steps : [];
    }

    /**
     * @return array<int, string>
     */
    public function citations(): array
    {
        $citations = $this->result['citations'] ?? [];

        return is_array($citations) ? array_map('strval', $citations) : [];
    }

    public function explanation(): ?string
    {
        $explanation = $this->result['explanation'] ?? null;

        return is_string($explanation) ? $explanation : null;
    }

    /**
     * The value to prefill a field with: whatever was last submitted, else the
     * field's own default.
     */
    public function old(string $name, mixed $default = null): mixed
    {
        return old($name, $default);
    }

    protected function viewName(): string
    {
        return static::TOOL === 'compliance-calendar' ? 'compliance-calendar' : static::TOOL.'-calculator';
    }

    /**
     * Null when routes are disabled, which is the default. The template then
     * renders the form without an action, so it posts to the current URL and
     * the host application can handle it - rather than pointing at a route
     * that does not exist and throwing during render.
     */
    private function routeUrl(): ?string
    {
        // The `web` block's keys default to null rather than being absent, so
        // config()'s own default never fires - hence the explicit coalescing.
        $prefix = config('payroll.routes.web.name_prefix')
            ?? config('payroll.routes.name_prefix')
            ?? 'payroll.';

        $name = $prefix.static::TOOL;

        return Route::has($name) ? route($name) : null;
    }
}
