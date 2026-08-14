<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Rate tables
    |--------------------------------------------------------------------------
    |
    | Leave this null to use the dated statutory tables bundled with
    | crmleaf/payroll-core, which is what almost everyone wants. Point it at a
    | directory of your own JSON tables to override them - useful if you need a
    | notification the package has not shipped yet, or if you maintain a
    | private schedule (a state PT slab your auditor reads differently).
    |
    | Overriding is all-or-nothing per directory: the repository reads
    | <path>/<key>.json, so a partial directory means a missing-table error for
    | anything you did not copy across.
    |
    */

    'rates_path' => env('PAYROLL_RATES_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Off by default, and deliberately so. Installing a library must never add
    | a publicly reachable endpoint to your application without you asking for
    | it - you may be embedding the calculators behind auth, or using nothing
    | but the facade. Flip `enabled` when you actually want the HTTP surface.
    |
    | `prefix`, `middleware` and `name_prefix` are the defaults both route
    | files inherit; the `web` and `api` blocks below override them where the
    | two need to differ (sessions and CSRF for one, stateless JSON for the
    | other). Anything you leave out of a block falls back to the value here.
    |
    */

    'routes' => [

        'enabled' => (bool) env('PAYROLL_ROUTES_ENABLED', false),

        'prefix' => env('PAYROLL_ROUTES_PREFIX', 'payroll'),

        // Applied to every route unless a block below says otherwise. Add your
        // own guard here - e.g. ['web', 'auth'] - to keep the tools internal.
        'middleware' => ['web'],

        // Every route name starts with this, so route('payroll.pf') resolves.
        // Keep the trailing dot; the route files concatenate directly.
        'name_prefix' => 'payroll.',

        'web' => [
            // HTML form posts, used by the Blade components when JavaScript is
            // unavailable. Needs the session for the post-redirect-get cycle.
            'enabled' => true,
            'prefix' => null,          // null → inherit the prefix above
            'middleware' => null,      // null → inherit the middleware above
            'name_prefix' => null,     // null → inherit the name prefix above
        ],

        'api' => [
            // JSON only. Throttled by default because the document endpoints
            // render PDFs, which is the one genuinely expensive thing here.
            'enabled' => true,
            'prefix' => env('PAYROLL_API_PREFIX', 'api/payroll'),
            'middleware' => ['api', 'throttle:60,1'],
            'name_prefix' => 'payroll.api.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Calculation defaults
    |--------------------------------------------------------------------------
    |
    | Used when a request leaves a field out. They are conveniences, not
    | statutory constants: nothing here changes what the law says, it only
    | decides which lawful option applies when the caller is silent.
    |
    */

    'defaults' => [

        // Professional tax is a state levy, so every PT-touching calculation
        // needs a state. There is no sensible national default; pick the state
        // your establishment is registered in.
        'state' => env('PAYROLL_STATE', 'Karnataka'),

        // Indian financial years run 1 April to 31 March. Leave this null to
        // let the engine work it out from today's date, or pin it to recompute
        // a closed year - arrears, a revised F&F, an audit of old challans.
        'financial_year' => env('PAYROLL_FINANCIAL_YEAR'),

        // 'new' or 'old'. The new regime has been the default regime under
        // section 115BAC since FY 2023-24, so it is the default here too.
        'regime' => env('PAYROLL_REGIME', 'new'),

        // Basic as a percentage of CTC. 40% is the common private-sector
        // structure; the Code on Wages pushes towards 50%, and some employers
        // already sit there. It drives PF, gratuity and leave encashment, so
        // change it knowingly.
        'basic_percent' => (float) env('PAYROLL_BASIC_PERCENT', 40),

        // HRA as a percentage of basic. 50% for the four metros (Delhi,
        // Mumbai, Kolkata, Chennai), 40% elsewhere, per the section 10(13A)
        // exemption limits most structures are built around.
        'hra_percent' => (float) env('PAYROLL_HRA_PERCENT', 50),

    ],

    /*
    |--------------------------------------------------------------------------
    | Company details
    |--------------------------------------------------------------------------
    |
    | Printed on payslips and invoices. These stay in your application and are
    | never transmitted anywhere - the documents are rendered by your server,
    | against your config, which is why there is no hosted document service and
    | therefore no API credential for a browser bundle to leak.
    |
    | `state_code` is the two-digit GST state code of the place of supply for
    | the supplier. It is what decides CGST+SGST versus IGST on an invoice, so
    | get it right: 29 is Karnataka, 27 Maharashtra, 07 Delhi, 33 Tamil Nadu.
    | If you set `gstin`, the first two digits of it win over this key.
    |
    */

    'company' => [

        'name' => env('PAYROLL_COMPANY_NAME', env('APP_NAME', 'Your Company')),

        'address' => env('PAYROLL_COMPANY_ADDRESS', ''),

        'gstin' => env('PAYROLL_COMPANY_GSTIN'),

        'state_code' => env('PAYROLL_COMPANY_STATE_CODE'),

        'pan' => env('PAYROLL_COMPANY_PAN'),

        // Absolute path, or a path relative to the application root. Dompdf
        // reads it off disk, so a URL will not do unless you enable remote
        // assets - which you should not.
        'logo_path' => env('PAYROLL_COMPANY_LOGO'),

        'email' => env('PAYROLL_COMPANY_EMAIL'),

        'phone' => env('PAYROLL_COMPANY_PHONE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | PDF rendering
    |--------------------------------------------------------------------------
    |
    | The only supported engine is dompdf, through barryvdh/laravel-dompdf,
    | and it is a *suggested* dependency rather than a required one: most
    | people install this package for the calculators and never render a
    | document, and a PDF library is a heavy thing to force on them. Ask for a
    | PDF without it and you get an exception telling you what to install.
    |
    | Set `engine` to 'none' to disable PDF output altogether - the generators
    | then still hand you the rendered HTML, which is enough if you print with
    | something else (Browsershot, Gotenberg, your own service).
    |
    */

    'pdf' => [

        'engine' => env('PAYROLL_PDF_ENGINE', 'dompdf'),

        'paper' => env('PAYROLL_PDF_PAPER', 'a4'),

        'orientation' => env('PAYROLL_PDF_ORIENTATION', 'portrait'),

        // Millimetres. Applied through the @page rule in the document
        // templates rather than a dompdf option, so they hold for HTML too.
        'margins' => [
            'top' => 12,
            'right' => 12,
            'bottom' => 14,
            'left' => 12,
        ],

        'options' => [
            // Leave this false. Turning it on lets a crafted template fetch
            // arbitrary URLs from inside your network.
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Rate table cache
    |--------------------------------------------------------------------------
    |
    | Rate tables are small JSON files read off disk and memoised for the life
    | of the request, so this is off by default and you will not miss it. Turn
    | it on if you are resolving many different `asOf` dates per request - a
    | payroll run recomputing a year of arrears, say - and want the decoded
    | tables to survive between requests.
    |
    | Cached entries are keyed by table and date, so enabling this cannot serve
    | you a superseded rate for a date; the worst it can do is hold a stale
    | copy of a table you have just edited. Clear the cache after a rate change.
    |
    */

    'cache' => [

        'enabled' => (bool) env('PAYROLL_CACHE_ENABLED', false),

        'store' => env('PAYROLL_CACHE_STORE'),

        // Seconds. A day is generous: statutory rates change a few times a
        // year, and always on a date you know in advance.
        'ttl' => (int) env('PAYROLL_CACHE_TTL', 86400),

        'prefix' => 'payroll.rates',

    ],

    /*
    |--------------------------------------------------------------------------
    | Blade components
    |--------------------------------------------------------------------------
    |
    | The components ship their own scoped CSS so they render sensibly in an
    | application with no CSS framework at all. If you would rather style them
    | yourself, set `styles` to false and target the .payroll-* classes.
    |
    */

    'components' => [

        'prefix' => 'payroll',

        'styles' => true,

        // Emitted as a data attribute the optional @crmleaf/payroll-js bundle
        // hooks on to. With the bundle absent the forms still post to the
        // routes and work; with it present they calculate without a round trip.
        'progressive_enhancement' => true,

    ],

];
