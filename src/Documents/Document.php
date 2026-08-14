<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Documents;

use Illuminate\Http\Response;

/**
 * A rendered document: the HTML, the data behind it, and lazily the PDF.
 *
 * The PDF is not produced until something asks for it. That is what lets an
 * application without dompdf still preview a payslip in the browser, and what
 * keeps a JSON request for the invoice totals from paying for a render it will
 * throw away.
 */
final class Document
{
    private ?string $pdf = null;

    /**
     * @param array<string, mixed> $data the view data, also the JSON shape
     */
    public function __construct(
        private readonly string $html,
        public readonly array $data,
        public readonly string $filename,
        private readonly PdfRenderer $renderer,
    ) {
    }

    public function html(): string
    {
        return $this->html;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Raw PDF bytes. Throws PdfEngineMissingException when no engine is
     * installed - deliberately, see that class.
     */
    public function pdf(): string
    {
        return $this->pdf ??= $this->renderer->render($this->html);
    }

    public function pdfAvailable(): bool
    {
        return $this->renderer->available();
    }

    public function download(?string $filename = null): Response
    {
        return $this->response($filename ?? $this->filename, 'attachment');
    }

    public function stream(?string $filename = null): Response
    {
        return $this->response($filename ?? $this->filename, 'inline');
    }

    public function save(string $path): string
    {
        $directory = \dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        file_put_contents($path, $this->pdf());

        return $path;
    }

    private function response(string $filename, string $disposition): Response
    {
        return new Response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ]);
    }

    /**
     * Kept only so a caller holding a Document can hand it straight back from
     * a controller and get the HTML preview rather than a fatal on a machine
     * with no PDF engine.
     */
    public function toResponse(): Response
    {
        return $this->pdfAvailable()
            ? $this->stream()
            : new Response($this->html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
