<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Laravel\Exceptions\PdfEngineMissingException;
use Illuminate\Contracts\Container\Container;

/**
 * Turns rendered HTML into PDF bytes, if and only if an engine is available.
 *
 * Kept behind its own class so the generators never touch dompdf directly and
 * the "is it installed?" question is asked in exactly one place.
 */
final class PdfRenderer
{
    /**
     * @param array<string, mixed> $config the payroll.pdf config block
     */
    public function __construct(
        private readonly array $config,
        private readonly Container $container,
    ) {
    }

    /**
     * Whether a PDF can actually be produced right now. Call it before
     * offering a download link rather than catching the exception afterwards.
     */
    /**
     * dompdf is referenced by name rather than by class constant throughout.
     * It is a suggested dependency, so its classes are legitimately absent -
     * from the autoloader at runtime and from static analysis alike - and a
     * `::class` reference would make the absence look like a mistake.
     */
    private const DOMPDF_CLASS = 'Barryvdh\\DomPDF\\PDF';

    private const DOMPDF_BINDING = 'dompdf.wrapper';

    public function available(): bool
    {
        return $this->engine() === 'dompdf' && class_exists(self::DOMPDF_CLASS);
    }

    public function engine(): string
    {
        $engine = $this->config['engine'] ?? 'dompdf';

        return is_string($engine) ? strtolower($engine) : 'dompdf';
    }

    public function render(string $html): string
    {
        $engine = $this->engine();

        if ($engine === 'none') {
            throw PdfEngineMissingException::disabled();
        }

        if ($engine !== 'dompdf') {
            throw PdfEngineMissingException::unknownEngine($engine);
        }

        if (!$this->container->bound(self::DOMPDF_BINDING) && !class_exists(self::DOMPDF_CLASS)) {
            throw PdfEngineMissingException::dompdf();
        }

        $pdf = $this->container->make(self::DOMPDF_BINDING);

        if (!is_object($pdf)) {
            throw PdfEngineMissingException::dompdf();
        }

        /** @var array<string, mixed> $options */
        $options = is_array($this->config['options'] ?? null) ? $this->config['options'] : [];

        // The wrapper's fluent API is unchanged across barryvdh/laravel-dompdf
        // v2 and v3; the guard above is what makes this call safe at runtime,
        // and static analysis cannot see a class that is not installed.
        $rendered = $pdf
            ->setOptions($options)
            ->setPaper(
                (string) ($this->config['paper'] ?? 'a4'),
                (string) ($this->config['orientation'] ?? 'portrait'),
            )
            ->loadHTML($html)
            ->output();

        return (string) $rendered;
    }

    /**
     * Margins in millimetres, ready for a CSS `@page` rule. The templates
     * apply them rather than the engine so the HTML preview and the PDF have
     * the same geometry.
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public function margins(): array
    {
        /** @var array<string, mixed> $margins */
        $margins = is_array($this->config['margins'] ?? null) ? $this->config['margins'] : [];

        return [
            'top' => (float) ($margins['top'] ?? 12),
            'right' => (float) ($margins['right'] ?? 12),
            'bottom' => (float) ($margins['bottom'] ?? 14),
            'left' => (float) ($margins['left'] ?? 12),
        ];
    }
}
