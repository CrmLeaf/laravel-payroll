# Laravel Payroll

The Laravel bridge for [`crmleaf/payroll-core`](https://github.com/crmleaf/payroll-core): service
provider, config, optional routes, Blade components, and payslip and GST
invoice PDFs rendered inside your own application.

```bash
composer require crmleaf/laravel-payroll
php artisan vendor:publish --tag=payroll-config
```

> [!NOTE]
> Not on Packagist yet. Until it is, add both repositories to **your own
> project's** `composer.json` and the same `require` works, because Composer
> reads the git tags:
>
> ```json
> "repositories": [
>     { "type": "vcs", "url": "https://github.com/crmleaf/laravel-payroll.git" },
>     { "type": "vcs", "url": "https://github.com/crmleaf/payroll-core.git" }
> ]
> ```
>
> Both entries are needed, and they have to be in the root project: Composer
> ignores a `repositories` block inside an installed dependency, so listing only
> this package will not resolve `crmleaf/payroll-core`.

The provider is auto-discovered. Nothing else is required, and nothing
observable changes until you ask for it - no route, no migration, no
middleware.

## Calculate

```php
use Crmleaf\Payroll\Laravel\Facades\Payroll;
use Crmleaf\Payroll\Money;

$pf = Payroll::pf()->calculate(basicSalary: Money::fromRupees(30_000));

$pf->employeeContribution->format();   // "₹3,600.00"
$pf->explain();                        // the formula with the operands in it
$pf->citations();                      // the statute each step rests on
```

Every accessor on the facade - `pf()`, `esi()`, `tds()`, `incomeTax()`,
`gratuity()`, `bonus()`, `ctc()`, `leaveEncashment()`, `fnf()`,
`epfoPenalty()`, `professionalTax()`, `calendar()`, `roi()`, `savings()`,
`payslip()`, `invoice()` - resolves a singleton from the container, so you can
rebind any of them in a test or point the rate repository at tables of your own
through `payroll.rates_path`.

Pass `asOf:` anywhere a statutory rate is involved to recompute a closed
period at the rates that were in force then - a revised F&F, an arrear paid in
a later year, an audit of last year's challans.

## Routes

Off by default. A library has no business adding a public endpoint to your
application uninvited.

```php
// config/payroll.php
'routes' => ['enabled' => true, 'middleware' => ['web', 'auth']],
```

That registers a POST endpoint per tool under `payroll/*` (HTML) and
`api/payroll/*` (JSON), named `payroll.pf` and `payroll.api.pf` respectively.
Everything is POST: these are calculations, and salary figures have no business
in an access log.

```
POST /api/payroll/pf        {"basic_salary": 30000}
POST /api/payroll/gratuity  {"last_drawn_salary": 45000, "years_of_service": 7, "months_of_service": 8}
POST /api/payroll/invoice   → application/pdf
```

Statutory *ineligibility* comes back as a 200 with a zero and a reason - three
years' service earns no gratuity, and that is an answer, not an error. Only
caller mistakes are 422s, and the validation messages say which rule you have
run into.

## Blade components

```blade
<x-payroll::gratuity-calculator />
<x-payroll-ctc-calculator />          {{-- same component, prefixed alias --}}
```

They work with JavaScript switched off: the form posts to the tool's route, the
controller redirects back, and the component renders the result out of the
session. Load `@crmleaf/payroll-js` and the same markup calculates in the
browser instead - an enhancement over a working baseline, not the only path.

The styles are scoped, framework-agnostic plain CSS emitted `@once` per page.
Redefine the `--payroll-*` custom properties to reskin them, or set
`payroll.components.styles` to false and serve `payroll-assets` yourself.

## Documents

```php
$payslip = Payroll::payslip()->fromCtc(
    ctc: Payroll::ctc()->calculate(annualCtc: Money::fromRupees(12_00_000)),
    employee: ['name' => 'R Iyer', 'code' => 'EMP-0042', 'uan' => '100123456789'],
    month: '2025-04',
);

return $payslip->download();          // or ->stream(), ->html(), ->save($path)
```

```php
$invoice = Payroll::invoice()->generate(
    number: 'INV/2025/0001',
    lines: [[
        'description' => 'Payroll subscription',
        'hsn' => '997331',
        'quantity' => 1,
        'unit_price' => 10_000,
        'gst_rate' => 18,
    ]],
    recipient: ['name' => 'Acme Pvt Ltd', 'gstin' => '27AAACT2727Q1ZW'],
);
```

The invoice decides CGST+SGST versus IGST by comparing the supplier's state
code with the place of supply - it is not a setting, it follows from the
supply - and carries the HSN/SAC per line, the rate-wise summary rule 46 asks
for, the round-off under section 170, and the total in words.

PDF rendering needs [`barryvdh/laravel-dompdf`](https://github.com/barryvdh/laravel-dompdf),
which is **suggested rather than required**: most installations use the
calculators and never render a document. Ask for a PDF without it and you get
an exception naming the package to install. `->html()` works either way.

Your company details, GSTIN and logo live in published config and never leave
your infrastructure. There is no hosted document service, and therefore no
credential for a browser bundle to leak.

## Publishing

| Tag | What it copies |
| --- | --- |
| `payroll-config` | `config/payroll.php` - heavily commented; publish this one |
| `payroll-views` | the components and document templates, forked into your app |
| `payroll-assets` | the standalone stylesheet, for strict CSP setups |
| `payroll-lang` | translation strings |

Publishing views forks the document templates, which then stop receiving fixes
when a GST or payroll rule changes. Do it to change the branding, not to change
the arithmetic - that lives in the calculators, where it is tested.

## Licence

MIT. See [LICENSE](LICENSE).
