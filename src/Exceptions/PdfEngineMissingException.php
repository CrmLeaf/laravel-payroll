<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Exceptions;

use Crmleaf\Payroll\Exceptions\PayrollException;

/**
 * A PDF was asked for and no PDF engine is installed.
 *
 * This is thrown rather than silently returning HTML because the two are not
 * interchangeable at the point of use: a route that promises application/pdf
 * and hands back markup produces a broken download, not a degraded one. The
 * message names the exact package to install, since the whole reason for the
 * failure is that we refused to make every installation carry it.
 */
final class PdfEngineMissingException extends \RuntimeException implements PayrollException
{
    public static function dompdf(): self
    {
        return new self(
            'PDF generation needs the dompdf bridge, which crmleaf/laravel-payroll suggests rather than '
            .'requires - most installations use the calculators and never render a document. Install it with '
            .'"composer require barryvdh/laravel-dompdf", or set payroll.pdf.engine to "none" and render the '
            .'HTML from ->html() with an engine of your own.',
        );
    }

    public static function disabled(): self
    {
        return new self(
            'PDF generation is switched off: payroll.pdf.engine is set to "none". Set it back to "dompdf" '
            .'(and install barryvdh/laravel-dompdf) to render PDFs, or call ->html() instead.',
        );
    }

    public static function unknownEngine(string $engine): self
    {
        return new self(sprintf(
            'Unknown PDF engine "%s" in payroll.pdf.engine. Supported values are "dompdf" and "none".',
            $engine,
        ));
    }
}
