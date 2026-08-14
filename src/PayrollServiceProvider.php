<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel;

use Crmleaf\Payroll\Laravel\Documents\InvoiceGenerator;
use Crmleaf\Payroll\Laravel\Documents\PayslipGenerator;
use Crmleaf\Payroll\Laravel\Documents\PdfRenderer;
use Crmleaf\Payroll\Laravel\View\Components;
use Crmleaf\Payroll\Rates\RateRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the framework-agnostic engine into a Laravel application.
 *
 * The guiding rule is that installing this package changes nothing observable
 * until you ask it to. It binds services, publishes files you may copy, and
 * registers view namespaces - but it adds no route, no migration and no
 * middleware of its own unless `payroll.routes.enabled` says so.
 */
final class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Blade components, keyed by the tag suffix they are registered under.
     * `<x-payroll-pf-calculator />` and `<x-payroll::pf-calculator />` both
     * resolve, the first through the prefix, the second through the namespace.
     *
     * @var array<string, class-string>
     */
    private const COMPONENTS = [
        'pf-calculator' => Components\PfCalculator::class,
        'esi-calculator' => Components\EsiCalculator::class,
        'tds-calculator' => Components\TdsCalculator::class,
        'income-tax-calculator' => Components\IncomeTaxCalculator::class,
        'gratuity-calculator' => Components\GratuityCalculator::class,
        'bonus-calculator' => Components\BonusCalculator::class,
        'ctc-calculator' => Components\CtcCalculator::class,
        'leave-encashment-calculator' => Components\LeaveEncashmentCalculator::class,
        'fnf-calculator' => Components\FnfCalculator::class,
        'epfo-penalty-calculator' => Components\EpfoPenaltyCalculator::class,
        'professional-tax-calculator' => Components\ProfessionalTaxCalculator::class,
        'compliance-calendar' => Components\ComplianceCalendar::class,
        'roi-calculator' => Components\RoiCalculator::class,
        'savings-calculator' => Components\SavingsCalculator::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/payroll.php'), 'payroll');

        // One repository per application. It memoises decoded rate tables, so
        // sharing it is the difference between reading professional-tax.json
        // once and reading it once per employee in a payroll run.
        $this->app->singleton(RateRepository::class, function ($app): RateRepository {
            /** @var ConfigRepository $config */
            $config = $app->make('config');

            $path = $config->get('payroll.rates_path');

            return new RateRepository(is_string($path) && $path !== '' ? $path : null);
        });

        foreach (PayrollManager::map() as $class) {
            if (str_starts_with($class, __NAMESPACE__.'\\Documents\\')) {
                continue;
            }

            // Every calculator takes the repository as its only constructor
            // argument, so the container's own resolution is enough - the
            // explicit binding is here to make them shared rather than new
            // on each resolve, and to give tests one seam to swap.
            $this->app->singleton($class, static fn ($app) => new $class($app->make(RateRepository::class)));
        }

        $this->app->singleton(PdfRenderer::class, static fn ($app) => new PdfRenderer(
            $app->make('config')->get('payroll.pdf', []),
            $app,
        ));

        $this->app->singleton(PayslipGenerator::class, static fn ($app) => new PayslipGenerator(
            $app->make(PdfRenderer::class),
            $app->make('view'),
            $app->make('config')->get('payroll.company', []),
        ));

        $this->app->singleton(InvoiceGenerator::class, static fn ($app) => new InvoiceGenerator(
            $app->make(PdfRenderer::class),
            $app->make('view'),
            $app->make('config')->get('payroll.company', []),
        ));

        $this->app->singleton(PayrollManager::class, static fn ($app) => new PayrollManager($app));
        $this->app->alias(PayrollManager::class, 'payroll');
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'payroll');
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'payroll');

        $this->registerComponents();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    /**
     * The tags are separate on purpose. Publishing config is routine;
     * publishing views forks them, and a forked view stops receiving fixes to
     * the document templates when a GST rule changes. Nobody should have to
     * take that on just to change a colour.
     */
    private function registerPublishing(): void
    {
        $this->publishes([
            $this->packagePath('config/payroll.php') => $this->app->configPath('payroll.php'),
        ], 'payroll-config');

        $this->publishes([
            $this->packagePath('resources/views') => $this->app->resourcePath('views/vendor/payroll'),
        ], 'payroll-views');

        $this->publishes([
            $this->packagePath('resources/assets') => $this->app->publicPath('vendor/payroll'),
        ], 'payroll-assets');

        $this->publishes([
            $this->packagePath('resources/lang') => $this->app->langPath('vendor/payroll'),
        ], 'payroll-lang');
    }

    private function registerComponents(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        $prefix = (string) $config->get('payroll.components.prefix', 'payroll');

        // The namespace form gives `<x-payroll::gratuity-calculator />`, which
        // is what the README documents; the class-level registration below
        // gives the hyphenated `<x-payroll-gratuity-calculator />` alias and,
        // more usefully, lets the component classes take constructor
        // dependencies out of the container.
        Blade::componentNamespace('Crmleaf\\Payroll\\Laravel\\View\\Components', $prefix);

        foreach (self::COMPONENTS as $tag => $class) {
            Blade::component($class, $prefix !== '' ? $prefix.'-'.$tag : $tag);
        }
    }

    private function registerRoutes(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        if (!$config->get('payroll.routes.enabled', false)) {
            return;
        }

        foreach (['web', 'api'] as $group) {
            if (!$config->get("payroll.routes.{$group}.enabled", true)) {
                continue;
            }

            Route::group([
                'prefix' => $config->get("payroll.routes.{$group}.prefix")
                    ?? $config->get('payroll.routes.prefix', 'payroll'),
                'middleware' => $config->get("payroll.routes.{$group}.middleware")
                    ?? $config->get('payroll.routes.middleware', ['web']),
                'as' => $config->get("payroll.routes.{$group}.name_prefix")
                    ?? $config->get('payroll.routes.name_prefix', 'payroll.'),
            ], fn () => $this->loadRoutesFrom($this->packagePath("routes/{$group}.php")));
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return array_merge(
            [PayrollManager::class, 'payroll', RateRepository::class, PdfRenderer::class],
            array_values(PayrollManager::map()),
        );
    }

    private function packagePath(string $relative): string
    {
        return \dirname(__DIR__).'/'.$relative;
    }
}
