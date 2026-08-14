<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Http\Controllers;

use Crmleaf\Payroll\Laravel\Http\Controllers\Concerns\RespondsWithResult;
use Crmleaf\Payroll\Laravel\PayrollManager;
use Illuminate\Routing\Controller;

/**
 * Base for the tool controllers. Holds the manager and the JSON/HTML decision,
 * and nothing else - every action below is meant to fit on a screen.
 */
abstract class PayrollController extends Controller
{
    use RespondsWithResult;

    public function __construct(protected readonly PayrollManager $payroll)
    {
    }
}
