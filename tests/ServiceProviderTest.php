<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Crmleaf\Payroll\Calculators\PfCalculator;
use Crmleaf\Payroll\Laravel\Documents\InvoiceGenerator;
use Crmleaf\Payroll\Laravel\Documents\PayslipGenerator;
use Crmleaf\Payroll\Laravel\Documents\PdfRenderer;
use Crmleaf\Payroll\Laravel\PayrollManager;
use Crmleaf\Payroll\Laravel\PayrollServiceProvider;
use Crmleaf\Payroll\Rates\RateRepository;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;

final class ServiceProviderTest extends TestCase
{
    public function testConfigIsMerged(): void
    {
        $this->assertSame('payroll', config('payroll.routes.prefix'));
        $this->assertSame('Karnataka', config('payroll.defaults.state'));
        $this->assertSame('dompdf', config('payroll.pdf.engine'));
        $this->assertIsArray(config('payroll.pdf.margins'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function boundServices(): array
    {
        $cases = ['manager' => [PayrollManager::class], 'rates' => [RateRepository::class], 'pdf' => [PdfRenderer::class]];

        foreach (PayrollManager::map() as $slug => $class) {
            $cases[$slug] = [$class];
        }

        return $cases;
    }

    #[DataProvider('boundServices')]
    public function testEveryServiceResolves(string $class): void
    {
        $this->assertInstanceOf($class, $this->app->make($class));
    }

    #[DataProvider('boundServices')]
    public function testEveryServiceIsASingleton(string $class): void
    {
        // Sharing matters more than it looks: the rate repository memoises
        // decoded JSON, and a payroll run resolving a fresh one per employee
        // reads professional-tax.json once per employee instead of once.
        $this->assertSame($this->app->make($class), $this->app->make($class));
    }

    public function testCalculatorsShareTheSameRateRepository(): void
    {
        $repository = $this->app->make(RateRepository::class);

        $reflection = new \ReflectionProperty(PfCalculator::class, 'rates');

        $this->assertSame($repository, $reflection->getValue($this->app->make(PfCalculator::class)));
    }

    public function testTheManagerIsAliasedForResolutionByName(): void
    {
        $this->assertInstanceOf(PayrollManager::class, $this->app->make('payroll'));
        $this->assertSame($this->app->make('payroll'), $this->app->make(PayrollManager::class));
    }

    public function testDocumentGeneratorsAreWiredWithTheConfiguredCompany(): void
    {
        config()->set('payroll.company.name', 'Testbench Industries');

        // Rebound so the generator picks the changed config up, which is what a
        // host application does in a service provider of its own.
        $this->app->forgetInstance(PayslipGenerator::class);
        $this->app->forgetInstance(InvoiceGenerator::class);

        $document = $this->app->make(PayslipGenerator::class)->generate(
            employee: ['name' => 'A Sharma'],
            month: '2025-04',
            earnings: ['Basic' => 20000],
        );

        $this->assertStringContainsString('Testbench Industries', $document->html());
    }

    public function testViewsAreRegisteredUnderThePayrollNamespace(): void
    {
        $this->assertTrue(view()->exists('payroll::components.pf-calculator'));
        $this->assertTrue(view()->exists('payroll::documents.payslip'));
        $this->assertTrue(view()->exists('payroll::documents.invoice'));
        $this->assertTrue(view()->exists('payroll::standalone'));
    }

    public function testTranslationsAreRegisteredUnderThePayrollNamespace(): void
    {
        $this->assertSame('Calculate', trans('payroll::payroll.actions.calculate'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publishTags(): array
    {
        return [
            'config' => ['payroll-config'],
            'views' => ['payroll-views'],
            'assets' => ['payroll-assets'],
            'lang' => ['payroll-lang'],
        ];
    }

    #[DataProvider('publishTags')]
    public function testEachPublishTagIsRegisteredSeparately(string $tag): void
    {
        $paths = ServiceProvider::pathsToPublish(PayrollServiceProvider::class, $tag);

        $this->assertNotEmpty($paths, sprintf('Nothing is published under the "%s" tag.', $tag));

        foreach (array_keys($paths) as $source) {
            $this->assertFileExists($source);
        }
    }

    public function testPublishingConfigTargetsTheApplicationConfigPath(): void
    {
        $paths = ServiceProvider::pathsToPublish(PayrollServiceProvider::class, 'payroll-config');

        $this->assertSame([$this->app->configPath('payroll.php')], array_values($paths));
    }

    public function testPublishTagsDoNotOverlap(): void
    {
        // Separate tags are the point: publishing config is routine, publishing
        // views forks the document templates and stops them receiving fixes.
        $config = ServiceProvider::pathsToPublish(PayrollServiceProvider::class, 'payroll-config');
        $views = ServiceProvider::pathsToPublish(PayrollServiceProvider::class, 'payroll-views');

        $this->assertEmpty(array_intersect_key($config, $views));
    }
}
