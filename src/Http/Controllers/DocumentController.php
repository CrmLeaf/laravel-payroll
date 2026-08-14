<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\InvoiceRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\PayslipRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Payslips and GST invoices.
 *
 * These are the only endpoints that do real work per request, which is why the
 * API route group is throttled by default. They also render entirely inside
 * your application: nothing is posted to a document service, so no credential
 * for one has to exist anywhere a browser can reach.
 */
final class DocumentController extends PayrollController
{
    public function payslip(PayslipRequest $request): JsonResponse|Response
    {
        $document = $this->payroll->payslip()->generate(...$request->arguments());

        return $this->respondWithDocument($request, $document, $request->wantsPdf());
    }

    public function invoice(InvoiceRequest $request): JsonResponse|Response
    {
        $document = $this->payroll->invoice()->generate(...$request->arguments());

        return $this->respondWithDocument($request, $document, $request->wantsPdf(), [
            // Surfaced separately because it is the thing most likely to be
            // wrong, and the caller should be able to assert on it without
            // parsing the rendered invoice.
            'tax_type' => $document->toArray()['tax_type'] ?? null,
            'inter_state' => $document->toArray()['inter_state'] ?? null,
        ]);
    }
}
