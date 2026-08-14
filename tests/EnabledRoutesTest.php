<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The other half of RoutesTest: with the config flag on, every tool is
 * reachable, under the configured prefix and name prefix.
 */
final class EnabledRoutesTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->enableRoutes($app);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function tools(): array
    {
        $tools = [
            'pf', 'esi', 'professional-tax', 'tds', 'income-tax', 'ctc', 'gratuity', 'bonus',
            'leave-encashment', 'fnf', 'epfo-penalty', 'compliance-calendar', 'roi', 'savings',
            'payslip', 'invoice',
        ];

        return array_combine($tools, array_map(static fn (string $t) => [$t], $tools));
    }

    #[DataProvider('tools')]
    public function testEveryToolHasAWebAndAnApiRoute(string $tool): void
    {
        $this->assertTrue(Route::has('payroll.'.$tool), 'Missing web route for '.$tool);
        $this->assertTrue(Route::has('payroll.api.'.$tool), 'Missing API route for '.$tool);
    }

    public function testRoutesSitUnderTheConfiguredPrefixes(): void
    {
        $this->assertSame('payroll/pf', Route::getRoutes()->getByName('payroll.pf')?->uri());
        $this->assertSame('api/payroll/pf', Route::getRoutes()->getByName('payroll.api.pf')?->uri());
    }

    public function testTheApiGroupCarriesItsOwnMiddleware(): void
    {
        $api = Route::getRoutes()->getByName('payroll.api.pf');

        $this->assertNotNull($api);
        $this->assertContains('api', $api->gatherMiddleware());
        $this->assertNotContains('web', $api->gatherMiddleware());
    }

    public function testEveryEndpointIsPostOnly(): void
    {
        // Salary figures have no business in an access log or a browser
        // history, which is what a GET with a query string would put them in.
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (!str_starts_with((string) $route->getName(), 'payroll.')) {
                continue;
            }

            $this->assertSame(['POST'], array_values(array_diff($route->methods(), ['HEAD'])));
        }
    }
}
