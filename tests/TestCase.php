<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Crmleaf\Payroll\Laravel\Facades\Payroll;
use Crmleaf\Payroll\Laravel\PayrollServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

require_once __DIR__.'/autoload.php';

abstract class TestCase extends BaseTestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PayrollServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['Payroll' => Payroll::class];
    }

    /**
     * Testbench boots without an application key, and the web middleware group
     * encrypts cookies, so an HTML form post would fail on the session rather
     * than on anything this package does.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_pad('crmleaf-payroll-testbench', 32, '.')));
    }

    /**
     * A date inside a financial year every bundled rate table covers.
     *
     * The income-tax table is versioned per Finance Act, so it necessarily
     * stops at the last year Parliament has legislated. Anything exercising a
     * calculation that reaches income tax pins its as-of date here rather than
     * defaulting to today, which would turn "the tables have not been updated
     * for the new year yet" into a failing test of this package.
     */
    protected function ratedDate(): string
    {
        return '2025-06-01';
    }

    /**
     * Turn the routes on for a test that needs them. They are off by default -
     * that is the behaviour under test in RoutesTest - so anything exercising
     * an endpoint has to ask for them explicitly, exactly as a host
     * application would.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function enableRoutes($app, bool $api = true, bool $web = true): void
    {
        $app['config']->set('payroll.routes.enabled', true);
        $app['config']->set('payroll.routes.api.enabled', $api);
        $app['config']->set('payroll.routes.web.enabled', $web);

        // Testbench has no rate limiter configured, and the default API
        // middleware stack references one. Dropping the throttle keeps the
        // test about the endpoint rather than about the limiter.
        $app['config']->set('payroll.routes.api.middleware', ['api']);
    }
}
