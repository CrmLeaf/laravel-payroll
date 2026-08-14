<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Crmleaf\Payroll\Money;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Shared plumbing for the document generators: company details, the logo, and
 * turning a Blade template into a Document.
 */
abstract class DocumentGenerator
{
    /**
     * @param array<string, mixed> $company the payroll.company config block
     */
    public function __construct(
        protected readonly PdfRenderer $renderer,
        protected readonly ViewFactory $views,
        protected readonly array $company = [],
    ) {
    }

    /**
     * The template this generator renders. Publishing `payroll-views` forks it
     * into the application, where Laravel's view resolution finds it first.
     */
    abstract protected function view(): string;

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function company(array $overrides = []): array
    {
        $company = array_merge($this->company, array_filter(
            $overrides,
            static fn ($value) => $value !== null && $value !== '',
        ));

        $company['logo'] = $this->logo($company['logo_path'] ?? null);

        return $company;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function document(array $data, string $filename): Document
    {
        $html = $this->views->make($this->view(), $data + [
            'margins' => $this->renderer->margins(),
        ])->render();

        return new Document($html, $data, $filename, $this->renderer);
    }

    /**
     * Inline the logo as a data URI.
     *
     * Dompdf runs with remote assets disabled - it has to, or a template
     * becomes an SSRF primitive - so a `<img src="https://...">` silently
     * renders as a broken box. Reading the file and embedding it sidesteps the
     * question entirely and works identically in the HTML preview.
     */
    protected function logo(mixed $path): ?string
    {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        $resolved = str_starts_with($path, '/') ? $path : base_path($path);

        if (!is_file($resolved) || !is_readable($resolved)) {
            return null;
        }

        $contents = file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        $mime = match (strtolower(pathinfo($resolved, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => null,
        };

        return $mime === null ? null : 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    protected static function money(mixed $value): Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return Money::fromRupees($value);
        }

        return Money::zero();
    }

    /**
     * Strip anything that would make a filename awkward on a Windows share or
     * in a Content-Disposition header.
     */
    protected static function slug(string $value): string
    {
        $value = (string) preg_replace('/[^A-Za-z0-9]+/', '-', $value);

        return trim(strtolower($value), '-') ?: 'document';
    }
}
