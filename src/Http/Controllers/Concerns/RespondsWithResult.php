<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers\Concerns;

use Crmleaf\Payroll\Contracts\CalculationResult;
use Crmleaf\Payroll\Laravel\Support\ResultSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The one decision every tool controller has to make: JSON or HTML.
 *
 * The HTML path is a post-redirect-get rather than a rendered response,
 * because the Blade components are meant to be dropped into somebody else's
 * page. Sending them back to the page they came from - with the result and the
 * old input in the session - keeps the component where the author put it,
 * instead of replacing their layout with ours. A direct POST with no referer
 * (curl, a test, a bookmarked form) falls back to the standalone page.
 */
trait RespondsWithResult
{
    protected function respond(
        Request $request,
        string $tool,
        CalculationResult $result,
    ): JsonResponse|RedirectResponse|View {
        if ($request->expectsJson()) {
            return $this->json($tool, $result);
        }

        $payload = ResultSession::payload($tool, $result);

        if ($request->headers->has('referer')) {
            return back()->withInput()->with(ResultSession::KEY, $payload);
        }

        return view('payroll::standalone', $payload + ['input' => $request->all()]);
    }

    protected function json(string $tool, CalculationResult $result, int $status = 200): JsonResponse
    {
        return response()->json([
            'tool' => $tool,
            'data' => $result->jsonSerialize(),
        ], $status);
    }

    /**
     * Documents are the exception: there is a real file to hand back, so the
     * HTML path streams the PDF rather than redirecting, and the JSON path
     * returns the figures with the PDF base64-encoded alongside them.
     *
     * @param array<string, mixed> $extra
     */
    protected function respondWithDocument(
        Request $request,
        \Crmleaf\Payroll\Laravel\Documents\Document $document,
        bool $wantsPdf,
        array $extra = [],
    ): JsonResponse|Response {
        if ($request->expectsJson()) {
            return response()->json([
                'filename' => $document->filename,
                'pdf_available' => $document->pdfAvailable(),
                'data' => $this->serialise($document->toArray()),
                'html' => $wantsPdf ? null : $document->html(),
                'pdf_base64' => $wantsPdf && $document->pdfAvailable() ? base64_encode($document->pdf()) : null,
            ] + $extra);
        }

        if (!$wantsPdf) {
            return new Response($document->html(), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return $document->stream();
    }

    /**
     * Flatten Money and DateTimeImmutable out of a document's view data so it
     * can go over the wire. The generators keep value objects in there because
     * the Blade templates want them; JSON does not.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function serialise(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $out[$key] = match (true) {
                $value instanceof \Crmleaf\Payroll\Money => [
                    'amount' => $value->toRupees(),
                    'formatted' => $value->format(),
                ],
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                $value instanceof \Crmleaf\Payroll\Laravel\Documents\InvoiceLine => $value->toArray(),
                is_array($value) => $this->serialise($value),
                default => $value,
            };
        }

        return $out;
    }
}
