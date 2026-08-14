{{--
    The fallback page for a form POST with no referer - a curl, a test, a
    bookmarked form. The normal HTML path redirects back to the page the
    component was embedded in; there is no such page here, so the component is
    rendered on its own with the result already in hand.

    Deliberately a bare document rather than an extension of the application's
    layout: this package has no business guessing what your layout is called.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ \Illuminate\Support\Str::headline($tool) }} - result</title>
    <style>
        body {
            margin: 0;
            padding: 2rem 1rem;
            background: #eef1f4;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }

        main { max-width: 46rem; margin: 0 auto; }

        @media (prefers-color-scheme: dark) {
            body { background: #0d1013; }
        }
    </style>
</head>
<body>
    <main>
        @php
            $component = $tool === 'compliance-calendar' ? $tool : $tool.'-calculator';
        @endphp

        <x-dynamic-component
            :component="config('payroll.components.prefix', 'payroll').'::'.$component"
            :result="$result"
        />
    </main>
</body>
</html>
