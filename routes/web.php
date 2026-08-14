<?php

declare(strict_types=1);

use Crmleaf\Payroll\Laravel\Http\Controllers\ComplianceController;
use Crmleaf\Payroll\Laravel\Http\Controllers\DocumentController;
use Crmleaf\Payroll\Laravel\Http\Controllers\PlanningController;
use Crmleaf\Payroll\Laravel\Http\Controllers\SalaryController;
use Crmleaf\Payroll\Laravel\Http\Controllers\SettlementController;
use Crmleaf\Payroll\Laravel\Http\Controllers\StatutoryController;
use Crmleaf\Payroll\Laravel\Http\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payroll web routes
|--------------------------------------------------------------------------
|
| These exist so the Blade components work with JavaScript switched off. Each
| one is the form target of the matching component: the form posts, the
| controller calculates, and the response redirects back to the page the form
| was on with the result in the session. Turn JavaScript on and the optional
| @crmleaf/payroll-js bundle intercepts the submit and calculates in the
| browser, but the route stays the fallback rather than becoming dead weight.
|
| Same names as the API routes, under whatever name prefix you configure, so
| route('payroll.pf') and route('payroll.api.pf') sit side by side.
|
*/

Route::post('pf', [StatutoryController::class, 'pf'])->name('pf');
Route::post('esi', [StatutoryController::class, 'esi'])->name('esi');
Route::post('professional-tax', [StatutoryController::class, 'professionalTax'])->name('professional-tax');

Route::post('tds', [TaxController::class, 'tds'])->name('tds');
Route::post('income-tax', [TaxController::class, 'incomeTax'])->name('income-tax');

Route::post('ctc', [SalaryController::class, 'ctc'])->name('ctc');

Route::post('gratuity', [SettlementController::class, 'gratuity'])->name('gratuity');
Route::post('bonus', [SettlementController::class, 'bonus'])->name('bonus');
Route::post('leave-encashment', [SettlementController::class, 'leaveEncashment'])->name('leave-encashment');
Route::post('fnf', [SettlementController::class, 'fnf'])->name('fnf');
Route::post('epfo-penalty', [SettlementController::class, 'epfoPenalty'])->name('epfo-penalty');

Route::post('compliance-calendar', [ComplianceController::class, 'calendar'])->name('compliance-calendar');

Route::post('roi', [PlanningController::class, 'roi'])->name('roi');
Route::post('savings', [PlanningController::class, 'savings'])->name('savings');

Route::post('payslip', [DocumentController::class, 'payslip'])->name('payslip');
Route::post('invoice', [DocumentController::class, 'invoice'])->name('invoice');
