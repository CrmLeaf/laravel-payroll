<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\CtcRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Salary structuring: the one calculation that pulls the others together.
 */
final class SalaryController extends PayrollController
{
    public function ctc(CtcRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'ctc', $this->payroll->ctc()->calculate(...$request->arguments()));
    }
}
