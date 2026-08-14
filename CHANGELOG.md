# Changelog

Notable changes to `crmleaf/laravel-payroll`.

Format per [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
per [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This package contains no arithmetic: every figure it renders comes from
`crmleaf/payroll-core`. A change to a published result belongs there, and is
listed in that package's changelog. What changes here is how the figures reach
your application — the provider, the config, the routes, the components and the
documents.

## [Unreleased]

## [1.0.0] - 2026-08-14

### Added

- `PayrollServiceProvider`, auto-discovered, binding every calculator into the
  container.
- A `Payroll` facade over the same calculators.
- Publishable config (`config/payroll.php`), covering the rate-table path, the
  route groups, and the company details the documents render from.
- Optional JSON API and web routes, **off by default**. Installing this package
  adds no endpoint to your application until `payroll.routes.enabled` is turned
  on; a library that opens a public route unasked has made a security decision
  on your behalf.
- Blade components for thirteen calculators, plus a standalone layout for
  rendering one outside your own chrome.
- Payslip and GST invoice documents, rendered to HTML always and to PDF when
  `barryvdh/laravel-dompdf` is installed. Both render **inside your own
  application** against your own config: there is deliberately no hosted
  document service, because a front-end posting to a central PDF endpoint would
  ship a token in every visitor's JavaScript bundle.
- English translation strings for every rendered label.

[Unreleased]: https://github.com/crmleaf/laravel-payroll/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/crmleaf/laravel-payroll/releases/tag/v1.0.0
