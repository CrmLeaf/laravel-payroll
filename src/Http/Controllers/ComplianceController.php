<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\ComplianceCalendarRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Statutory due dates for a financial year.
 */
final class ComplianceController extends PayrollController
{
    public function calendar(ComplianceCalendarRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond(
            $request,
            'compliance-calendar',
            $this->payroll->calendar()->forFinancialYear(...$request->arguments()),
        );
    }
}
