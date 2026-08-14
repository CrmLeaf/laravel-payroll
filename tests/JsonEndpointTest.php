<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\Tests;

final class JsonEndpointTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->enableRoutes($app);
    }

    public function testPfRoundTripReturnsTheFigureAndItsWorking(): void
    {
        $response = $this->postJson('/api/payroll/pf', [
            'basic_salary' => 25000,
            'employer_restricts_to_ceiling' => false,
            'include_admin_charges' => false,
        ]);

        $response->assertOk()->assertJsonPath('tool', 'pf');

        // 12% of the full ₹25,000 basic; but the EPS share is 8.33% of the
        // ₹15,000 ceiling - ₹1,249.50 rounded to the rupee - not 8.33% of the
        // basic. That asymmetry is the one most implementations get wrong.
        $this->assertSame(3000.0, (float) $response->json('data.employee_contribution'));
        $this->assertSame(1250.0, (float) $response->json('data.employer_eps'));

        $this->assertNotEmpty($response->json('data.steps'));
        $this->assertNotEmpty($response->json('data.citations'));
        $this->assertIsString($response->json('data.explanation'));
    }

    public function testValidationFailureIsA422WithTheStatutoryExplanation(): void
    {
        $response = $this->postJson('/api/payroll/bonus', [
            'monthly_wages' => 15000,
            'bonus_rate' => 25,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('20%', (string) $response->json('errors.bonus_rate.0'));
    }

    public function testANegativeSalaryIsRejectedBeforeItReachesTheCalculator(): void
    {
        $this->postJson('/api/payroll/pf', ['basic_salary' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('basic_salary');
    }

    public function testStatutoryIneligibilityIsA200NotAnError(): void
    {
        // Three years' service earns no gratuity. That is a lawful answer with
        // a reason attached, not a client error, and the API must not dress it
        // up as one.
        $response = $this->postJson('/api/payroll/gratuity', [
            'last_drawn_salary' => 45000,
            'years_of_service' => 3,
        ]);

        $response->assertOk();
        $this->assertSame(0.0, (float) $response->json('data.gratuity'));
        $this->assertFalse($response->json('data.eligible'));
        $this->assertNotEmpty($response->json('data.ineligibility_reason'));
    }

    public function testAHistoricalAsOfDateUsesTheRatesInForceThen(): void
    {
        // ESI rates were cut on 1 July 2019, from 1.75%/4.75% to 0.75%/3.25%.
        // Recomputing a June 2018 contribution has to use the old pair - this
        // is what makes a revised F&F or an audit of an old challan possible.
        $current = $this->postJson('/api/payroll/esi', ['gross_wages' => 20000])->assertOk();

        $historical = $this->postJson('/api/payroll/esi', [
            'gross_wages' => 20000,
            'as_of' => '2018-06-01',
        ])->assertOk();

        $this->assertSame(0.75, (float) $current->json('data.employee_rate'));
        $this->assertSame(1.75, (float) $historical->json('data.employee_rate'));
        $this->assertSame(150.0, (float) $current->json('data.employee_contribution'));
        $this->assertSame(350.0, (float) $historical->json('data.employee_contribution'));
    }

    public function testTheInvoiceEndpointSplitsTaxByPlaceOfSupply(): void
    {
        config()->set('payroll.company.gstin', '29AAACT2727Q1ZW');

        $payload = [
            'number' => 'INV/2025/001',
            'lines' => [[
                'description' => 'Payroll software subscription',
                'hsn' => '997331',
                'quantity' => 1,
                'unit_price' => 10000,
                'gst_rate' => 18,
            ]],
            'recipient' => ['name' => 'Acme Pvt Ltd', 'state' => 'Karnataka'],
            'format' => 'html',
        ];

        $intra = $this->postJson('/api/payroll/invoice', $payload)->assertOk();
        $this->assertSame('CGST + SGST', $intra->json('tax_type'));
        $this->assertSame(900.0, (float) $intra->json('data.cgst.amount'));
        $this->assertSame(900.0, (float) $intra->json('data.sgst.amount'));
        $this->assertSame(0.0, (float) $intra->json('data.igst.amount'));

        $payload['recipient']['state'] = 'Maharashtra';
        $inter = $this->postJson('/api/payroll/invoice', $payload)->assertOk();
        $this->assertSame('IGST', $inter->json('tax_type'));
        $this->assertSame(1800.0, (float) $inter->json('data.igst.amount'));
        $this->assertSame(0.0, (float) $inter->json('data.cgst.amount'));
    }

    public function testThePayslipEndpointReturnsHtmlWhenAskedFor(): void
    {
        $response = $this->post('/payroll/payslip', [
            'employee' => ['name' => 'R Iyer', 'code' => 'EMP-0042'],
            'month' => '2025-04',
            'earnings' => ['Basic' => 30000, 'HRA' => 15000],
            'deductions' => ['Provident Fund' => 3600],
            'format' => 'html',
        ]);

        $response->assertOk();
        $response->assertSee('R Iyer');
        $response->assertSee('April 2025');
        // 45,000 earned less 3,600 deducted.
        $response->assertSee('41,400.00', false);
    }
}
