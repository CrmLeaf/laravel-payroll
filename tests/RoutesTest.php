<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Illuminate\Support\Facades\Route;

/**
 * A library must not add a publicly reachable endpoint to somebody's
 * application because they ran `composer require`. That is the whole of what
 * this file tests.
 */
final class RoutesTest extends TestCase
{
    public function testRoutesAreOffByDefault(): void
    {
        $this->assertFalse(config('payroll.routes.enabled'));

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $this->assertStringNotContainsString(
                'payroll',
                (string) $route->getName(),
                'The package registered a route without being asked to.',
            );
        }
    }

    public function testPostingToADisabledEndpointIs404(): void
    {
        $this->postJson('/api/payroll/pf', ['basic_salary' => 25000])->assertNotFound();
    }
}
