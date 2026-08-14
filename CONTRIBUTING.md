# Contributing to crmleaf/laravel-payroll

Thanks for taking the time. This package is the Laravel bridge for the CRMLeaf
payroll engine: a service provider, config, optional routes, Blade components
and PDF documents.

By participating you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## Where to open things

Open issues and pull requests **here**, against this repository. It holds its
own history and tags.

One thing belongs elsewhere: **this package contains no arithmetic.** If a
figure is wrong, the bug is in [`crmleaf/payroll-core`][core] and the fix
belongs there, with a test pinning the statute. What lives here is how figures
reach your application — binding, config, routes, components, documents. If you
find yourself computing in this package, the logic is in the wrong repository.

[core]: https://github.com/crmleaf/payroll-core

## Reproducing locally

`crmleaf/payroll-core` is not on Packagist yet, so `composer.json` points at its
git repository. That is all it takes:

```bash
git clone https://github.com/crmleaf/laravel-payroll.git
cd laravel-payroll
composer install
vendor/bin/phpunit
```

The suite runs on Orchestra Testbench, so it boots a real Laravel application —
no separate app needed.

```bash
vendor/bin/phpstan analyse            # level 8 over src/
vendor/bin/php-cs-fixer fix --dry-run --diff
composer validate --strict
```

CI runs these against PHP 8.2-8.4 and Laravel 10, 11 and 12. A change that
narrows what is supported has to say so in `composer.json`, not just pass on the
newest combination.

## Two constraints that are load-bearing

**Routes stay off by default.** `payroll.routes.enabled` is `false` in the
shipped config and `RoutesTest` asserts it. Installing this package must change
nothing observable in a consuming application until asked. A pull request that
flips a default to on will be sent back.

**Documents render in the consumer's application.** The payslip and invoice
generators run against the consuming app's own config and never call out. There
is no hosted document service and adding one is not a change we will take: a
front-end component posting to a central PDF endpoint ships a token in every
visitor's JavaScript bundle, in a page handling salary data.

## Pull requests

- **Branch from `main`.** Name it `feat/…`, `fix/…`, `docs/…` or `chore/…`.
- **One logical change per pull request.**
- **Write a test.** A bug fix needs one that fails before the fix.
- **Update `CHANGELOG.md`** under `## [Unreleased]`.
- **Mind the public API.** This package is installed into other people's
  applications; we follow [semantic versioning](https://semver.org). A changed
  component signature, config key or route name is breaking.

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org):

```
fix(documents): keep the payslip footer inside the page on dompdf 3
feat(blade): add a compliance-calendar component
docs(config): explain the rates_path override
```

## Reporting a wrong figure

Open it against [`crmleaf/payroll-core`][core], with the inputs, the figure you
got, the figure you expected, and the rule or notification that says so. That
turns a report into a test case.

## Security

Do not open public issues for vulnerabilities. See [SECURITY.md](SECURITY.md).

## Licence

Contributions are accepted under the [MIT Licence](LICENSE). You confirm you
have the right to submit the code under that licence.
