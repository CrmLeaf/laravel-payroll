<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;

final class BladeComponentTest extends TestCase
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
    public static function components(): array
    {
        $tags = [
            'pf-calculator', 'esi-calculator', 'tds-calculator', 'income-tax-calculator',
            'gratuity-calculator', 'bonus-calculator', 'ctc-calculator',
            'leave-encashment-calculator', 'fnf-calculator', 'epfo-penalty-calculator',
            'professional-tax-calculator', 'compliance-calendar', 'roi-calculator',
            'savings-calculator',
        ];

        return array_combine($tags, array_map(static fn (string $tag) => [$tag], $tags));
    }

    #[DataProvider('components')]
    public function testEachComponentRendersThroughTheNamespace(string $tag): void
    {
        $html = Blade::render('<x-payroll::'.$tag.' />');

        $this->assertStringContainsString('payroll-tool', $html);
        $this->assertStringContainsString('<form method="POST"', $html);
        $this->assertStringContainsString('<button type="submit">', $html);
    }

    #[DataProvider('components')]
    public function testEachComponentAlsoRendersThroughThePrefixedAlias(string $tag): void
    {
        $this->assertStringContainsString('payroll-tool', Blade::render('<x-payroll-'.$tag.' />'));
    }

    public function testTheFormPostsToTheToolRouteSoItWorksWithoutJavaScript(): void
    {
        $html = Blade::render('<x-payroll::gratuity-calculator />');

        $this->assertStringContainsString('action="'.route('payroll.gratuity').'"', $html);
        $this->assertStringContainsString('name="last_drawn_salary"', $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    public function testTheProgressiveEnhancementHookIsPresentButNotRequired(): void
    {
        $html = Blade::render('<x-payroll::pf-calculator />');

        $this->assertStringContainsString('data-payroll-tool="pf"', $html);
        $this->assertStringContainsString('data-payroll-form', $html);

        config()->set('payroll.components.progressive_enhancement', false);

        $this->assertStringNotContainsString(
            'data-payroll-tool',
            Blade::render('<x-payroll::pf-calculator />'),
        );
    }

    public function testAResultPassedInIsRendered(): void
    {
        $result = $this->app->make(\Crmleaf\Payroll\Calculators\GratuityCalculator::class)->calculate(
            lastDrawnSalary: \Crmleaf\Payroll\Money::fromRupees(45_000),
            yearsOfService: 7,
            monthsOfService: 8,
        );

        $html = Blade::render(
            '<x-payroll::gratuity-calculator :result="$result" />',
            ['result' => $result->jsonSerialize()],
        );

        $this->assertStringContainsString('₹2,07,692.31', $html);
        $this->assertStringContainsString('(15 × 45,000.00 × 8) ÷ 26', $html);
        $this->assertStringContainsString('Payment of Gratuity Act', $html);
    }

    public function testTheResultPanelIsHiddenUntilThereIsOne(): void
    {
        // Hidden rather than absent, so the payroll-js bundle has a container
        // to fill in when it calculates without a round trip.
        $this->assertMatchesRegularExpression(
            '/data-payroll-result\s+hidden/',
            Blade::render('<x-payroll::pf-calculator />'),
        );
    }

    public function testStylesAreEmittedOnceForManyComponentsOnAPage(): void
    {
        $html = Blade::render('<x-payroll::pf-calculator /><x-payroll::esi-calculator />');

        $this->assertSame(1, substr_count($html, '.payroll-tool__figure dt'));
    }

    public function testStylesCanBeSwitchedOffForApplicationsThatSupplyTheirOwn(): void
    {
        config()->set('payroll.components.styles', false);

        $html = Blade::render('<x-payroll::pf-calculator />');

        $this->assertStringNotContainsString('<style>', $html);
        $this->assertStringContainsString('payroll-tool', $html);
    }
}
