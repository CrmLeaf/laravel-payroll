<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\RoiRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\SavingsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The two business-case tools. No statute in sight - these are arithmetic on
 * assumptions the caller supplies, and the results say so.
 */
final class PlanningController extends PayrollController
{
    public function roi(RoiRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'roi', $this->payroll->roi()->calculate(...$request->arguments()));
    }

    public function savings(SavingsRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'savings', $this->payroll->savings()->calculate(...$request->arguments()));
    }
}
