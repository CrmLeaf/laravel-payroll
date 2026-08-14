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
| Payroll API routes
|--------------------------------------------------------------------------
|
| Loaded only when payroll.routes.enabled and payroll.routes.api.enabled are
| both true. The prefix, middleware and name prefix come from config and are
| already applied by the service provider, so nothing here hardcodes a path.
|
| Everything is POST. These are calculations, not resources - there is no
| identifier to GET, the inputs are numerous enough to be awkward in a query
| string, and salary figures have no business sitting in access logs.
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
