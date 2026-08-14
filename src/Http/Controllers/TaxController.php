<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\IncomeTaxRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\TdsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Income tax on salary, and the monthly instalment of it that section 192
 * requires the employer to withhold.
 */
final class TaxController extends PayrollController
{
    public function tds(TdsRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'tds', $this->payroll->tds()->calculate(...$request->arguments()));
    }

    public function incomeTax(IncomeTaxRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond(
            $request,
            'income-tax',
            $this->payroll->incomeTax()->calculate(...$request->arguments()),
        );
    }
}
