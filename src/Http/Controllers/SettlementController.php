<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Requests\BonusRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\EpfoPenaltyRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\FnfRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\GratuityRequest;
use Crmleaf\Payroll\Laravel\Http\Requests\LeaveEncashmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * What is owed when employment ends, plus the penalties for paying the
 * statutory dues late.
 *
 * Every one of these can legitimately return nought - three years' service
 * earns no gratuity, wages above ₹21,000 earn no statutory bonus, a challan
 * paid on time attracts no damages. Those are results with an explanation
 * attached, not errors, so nothing here maps them to a 4xx.
 */
final class SettlementController extends PayrollController
{
    public function gratuity(GratuityRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'gratuity', $this->payroll->gratuity()->calculate(...$request->arguments()));
    }

    public function bonus(BonusRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'bonus', $this->payroll->bonus()->calculate(...$request->arguments()));
    }

    public function leaveEncashment(LeaveEncashmentRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond(
            $request,
            'leave-encashment',
            $this->payroll->leaveEncashment()->calculate(...$request->arguments()),
        );
    }

    public function fnf(FnfRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond($request, 'fnf', $this->payroll->fnf()->calculate(...$request->arguments()));
    }

    public function epfoPenalty(EpfoPenaltyRequest $request): JsonResponse|RedirectResponse|View
    {
        return $this->respond(
            $request,
            'epfo-penalty',
            $this->payroll->epfoPenalty()->calculate(...$request->arguments()),
        );
    }
}
