<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

use Crmleaf\Payroll\Laravel\Facades\Payroll;
use Crmleaf\Payroll\Laravel\PayrollManager;
use Crmleaf\Payroll\Money;
use PHPUnit\Framework\Attributes\DataProvider;

final class FacadeTest extends TestCase
{
    /**
     * Every facade method, paired with the class it must hand back. The @method
     * docblocks on the facade are what an IDE reads; this is what proves they
     * are not lying.
     *
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function accessors(): array
    {
        $map = [
            'pf' => 'pf',
            'esi' => 'esi',
            'tds' => 'tds',
            'incomeTax' => 'income-tax',
            'gratuity' => 'gratuity',
            'bonus' => 'bonus',
            'ctc' => 'ctc',
            'leaveEncashment' => 'leave-encashment',
            'fnf' => 'fnf',
            'epfoPenalty' => 'epfo-penalty',
            'professionalTax' => 'professional-tax',
            'calendar' => 'compliance-calendar',
            'roi' => 'roi',
            'savings' => 'savings',
            'payslip' => 'payslip',
            'invoice' => 'invoice',
        ];

        $classes = PayrollManager::map();
        $cases = [];

        foreach ($map as $method => $slug) {
            $cases[$method] = [$method, $classes[$slug]];
        }

        return $cases;
    }

    #[DataProvider('accessors')]
    public function testEachAccessorResolvesItsDocumentedClass(string $method, string $class): void
    {
        $this->assertInstanceOf($class, Payroll::{$method}());
    }

    public function testTheDocblockCoversEveryPublicAccessor(): void
    {
        $docblock = (new \ReflectionClass(Payroll::class))->getDocComment();

        $this->assertIsString($docblock);

        foreach ((new \ReflectionClass(PayrollManager::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->isConstructor()) {
                continue;
            }

            $this->assertStringContainsString(
                $method->getName().'()',
                $docblock,
                sprintf('PayrollManager::%s() has no @method line on the facade.', $method->getName()),
            );
        }
    }

    public function testTheFacadeCalculatesThroughTheContainer(): void
    {
        $result = Payroll::gratuity()->calculate(
            lastDrawnSalary: Money::fromRupees(45_000),
            yearsOfService: 7,
            monthsOfService: 8,
        );

        $this->assertSame(8, $result->completedYears);
        $this->assertStringContainsString('(15 × 45,000.00 × 8) ÷ 26', $result->explain());
    }

    public function testTheAliasIsRegisteredForAutoDiscovery(): void
    {
        $this->assertSame(PayrollManager::class, Payroll::getFacadeRoot()::class);
    }
}
