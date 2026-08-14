<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests\Documents;

use Crmleaf\Payroll\Exceptions\InvalidInputException;
use Crmleaf\Payroll\Laravel\Documents\Document;
use Crmleaf\Payroll\Laravel\Documents\GstStateCodes;
use Crmleaf\Payroll\Laravel\Documents\InvoiceGenerator;
use Crmleaf\Payroll\Laravel\Exceptions\PdfEngineMissingException;
use Crmleaf\Payroll\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class InvoiceGeneratorTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // 29 is Karnataka. Everything below turns on whether the place of
        // supply matches it.
        $app['config']->set('payroll.company.gstin', '29AAACT2727Q1ZW');
        $app['config']->set('payroll.company.name', 'CRMLeaf Technologies');
    }

    private function generator(): InvoiceGenerator
    {
        return $this->app->make(InvoiceGenerator::class);
    }

    /**
     * @param array<int, array<string, mixed>>|null $lines
     * @param array<string, mixed> $extra
     */
    private function invoice(?array $lines = null, array $recipient = ['name' => 'Acme', 'state' => 'Karnataka'], array $extra = []): Document
    {
        return $this->generator()->generate(...array_merge([
            'number' => 'INV/2025/0001',
            'lines' => $lines ?? [[
                'description' => 'Payroll subscription',
                'hsn' => '997331',
                'quantity' => 1,
                'unit_price' => 10000,
                'gst_rate' => 18,
            ]],
            'recipient' => $recipient,
            'date' => '2025-06-10',
        ], $extra));
    }

    public function testIntraStateSupplySplitsIntoCgstAndSgst(): void
    {
        $data = $this->invoice()->toArray();

        $this->assertFalse($data['inter_state']);
        $this->assertSame('CGST + SGST', $data['tax_type']);
        $this->assertSame(900.0, $data['cgst']->toRupees());
        $this->assertSame(900.0, $data['sgst']->toRupees());
        $this->assertSame(0.0, $data['igst']->toRupees());
        $this->assertSame(11800.0, $data['grand_total']->toRupees());
    }

    public function testInterStateSupplyIsWhollyIgst(): void
    {
        $data = $this->invoice(recipient: ['name' => 'Acme', 'state' => 'Maharashtra'])->toArray();

        $this->assertTrue($data['inter_state']);
        $this->assertSame('IGST', $data['tax_type']);
        $this->assertSame(1800.0, $data['igst']->toRupees());
        $this->assertSame(0.0, $data['cgst']->toRupees());
        $this->assertSame(0.0, $data['sgst']->toRupees());

        // The tax is the same amount either way. What changes is who receives
        // it, which is exactly why the decision cannot be a preference.
        $this->assertSame(11800.0, $data['grand_total']->toRupees());
    }

    public function testTheRecipientGstinBeatsALooselyTypedStateName(): void
    {
        // 27 is Maharashtra. The GSTIN is the registration the return is filed
        // under, so it wins over the "Karnataka" somebody typed beside it.
        $data = $this->invoice(recipient: [
            'name' => 'Acme',
            'state' => 'Karnataka',
            'gstin' => '27AAACT2727Q1ZW',
        ])->toArray();

        $this->assertTrue($data['inter_state']);
        $this->assertSame('27', $data['place_of_supply_code']);
    }

    public function testAnExplicitPlaceOfSupplyOverridesTheRecipientAddress(): void
    {
        $data = $this->invoice(
            recipient: ['name' => 'Acme', 'state' => 'Karnataka'],
            extra: ['placeOfSupply' => '33'],
        )->toArray();

        $this->assertTrue($data['inter_state']);
        $this->assertSame('Tamil Nadu', $data['place_of_supply']);
    }

    public function testAnUnknownRecipientStateFallsBackToIntraStateAndSaysSo(): void
    {
        $data = $this->invoice(recipient: ['name' => 'Walk-in customer'])->toArray();

        $this->assertTrue($data['place_of_supply_assumed']);
        $this->assertFalse($data['inter_state']);
        $this->assertStringContainsString(
            'place of supply has been taken as the supplier',
            $this->invoice(recipient: ['name' => 'Walk-in customer'])->html(),
        );
    }

    public function testATaxedInvoiceWithoutASupplierStateIsRefused(): void
    {
        config()->set('payroll.company.gstin', null);
        config()->set('payroll.company.state_code', null);
        $this->app->forgetInstance(InvoiceGenerator::class);

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessageMatches('/supplier\'s state/');

        $this->invoice();
    }

    public function testEachLineCarriesItsOwnRateAndTheSummaryGroupsThem(): void
    {
        $document = $this->invoice([
            ['description' => 'Subscription', 'hsn' => '997331', 'quantity' => 1, 'unit_price' => 10000, 'gst_rate' => 18],
            ['description' => 'Support hours', 'hsn' => '997331', 'quantity' => 2, 'unit_price' => 2500, 'gst_rate' => 18],
            ['description' => 'Printed manual', 'hsn' => '4901', 'quantity' => 3, 'unit_price' => 400, 'gst_rate' => 5],
        ]);

        $data = $document->toArray();
        $summary = $data['hsn_summary'];

        // Two rows, not three: the two 18% service lines under the same SAC
        // collapse, the 5% goods line stays separate.
        $this->assertCount(2, $summary);

        $bySac = array_column($summary, null, 'hsn');
        $this->assertSame(15000.0, $bySac['997331']['taxable_value']->toRupees());
        $this->assertSame(1200.0, $bySac['4901']['taxable_value']->toRupees());
        $this->assertSame(2700.0, $bySac['997331']['total_tax']->toRupees());
        $this->assertSame(60.0, $bySac['4901']['total_tax']->toRupees());
    }

    public function testALineDiscountReducesTheTaxableValueBeforeTax(): void
    {
        $data = $this->invoice([[
            'description' => 'Subscription',
            'hsn' => '997331',
            'quantity' => 1,
            'unit_price' => 10000,
            'discount' => 1000,
            'gst_rate' => 18,
        ]])->toArray();

        $this->assertSame(9000.0, $data['taxable_value']->toRupees());
        $this->assertSame(1620.0, $data['total_tax']->toRupees());
    }

    public function testTheTotalIsRoundedToTheNearestRupeeAndTheDifferenceIsShown(): void
    {
        // 4,999.50 at 18% is 899.91 of tax and 5,899.41 payable, which rounds
        // down to 5,899 with a −0.41 round-off line. Section 170 permits the
        // rounding; showing it is what keeps the recipient's credit reconciling.
        $data = $this->invoice([[
            'description' => 'Subscription',
            'hsn' => '997331',
            'quantity' => 1,
            'unit_price' => 4999.50,
            'gst_rate' => 18,
        ]])->toArray();

        $this->assertSame(5899.41, $data['total_before_rounding']->toRupees());
        $this->assertSame(-0.41, $data['round_off']->toRupees());
        $this->assertSame(5899.0, $data['grand_total']->toRupees());
    }

    public function testTheAmountIsSpeltOutInIndianNumbering(): void
    {
        $data = $this->invoice([[
            'description' => 'Implementation',
            'hsn' => '998313',
            'quantity' => 1,
            'unit_price' => 1_000_000,
            'gst_rate' => 18,
        ]])->toArray();

        $this->assertSame('Rupees Eleven Lakh Eighty Thousand Only', $data['amount_in_words']);
    }

    public function testTheRenderedInvoiceCarriesWhatRule46Asks(): void
    {
        $html = $this->invoice()->html();

        foreach (['Tax invoice', 'INV/2025/0001', 'GSTIN: 29AAACT2727Q1ZW', 'Place of supply', '997331', 'CGST', 'SGST', 'Amount in words'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function testReverseChargeIsDeclaredOnTheFaceOfTheInvoice(): void
    {
        $html = $this->invoice(extra: ['reverseCharge' => true])->html();

        $this->assertStringContainsString('reverse charge', $html);
    }

    public function testAnEmptyInvoiceIsACallerError(): void
    {
        $this->expectException(InvalidInputException::class);

        $this->invoice([]);
    }

    public function testAskingForAPdfWithoutAnEngineExplainsWhatToInstall(): void
    {
        if (class_exists('Barryvdh\\DomPDF\\PDF')) {
            $this->markTestSkipped('dompdf is installed, so there is no missing engine to report.');
        }

        $this->expectException(PdfEngineMissingException::class);
        $this->expectExceptionMessageMatches('/barryvdh\/laravel-dompdf/');

        $this->invoice()->pdf();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stateCodes(): array
    {
        return [
            'karnataka' => ['Karnataka', '29'],
            'maharashtra' => ['Maharashtra', '27'],
            'delhi' => ['Delhi', '07'],
            'tamil nadu' => ['Tamil Nadu', '33'],
            'west bengal' => ['West Bengal', '19'],
        ];
    }

    #[DataProvider('stateCodes')]
    public function testStateNamesResolveToTheirGstCodes(string $name, string $code): void
    {
        $this->assertSame($code, GstStateCodes::codeForName($name));
        $this->assertSame($name, GstStateCodes::name($code));
    }
}
