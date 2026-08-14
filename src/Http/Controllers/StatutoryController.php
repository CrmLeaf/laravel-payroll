<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\EsiRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\PfRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\ProfessionalTaxRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The three statutory deductions that apply month after month: provident fund,
 * state insurance and professional tax.
 */
final class StatutoryController extends PayrollController
{
    public function pf(PfRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'pf', $this->payroll->pf()->calculate(...$request->arguments()));
    }

    public function esi(EsiRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'esi', $this->payroll->esi()->calculate(...$request->arguments()));
    }

    public function professionalTax(ProfessionalTaxRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond(
            $request,
            'professional-tax',
            $this->payroll->professionalTax()->calculate(...$request->arguments()),
        );
    }
}
