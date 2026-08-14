<?php

declare(strict_types=1);

/*
 * PSR-4 fallback for the package sources when the suite runs from the
 * monorepo root.
 *
 * The root composer.json maps only the test namespaces onto Composer's
 * autoloader, so `Crmleaf\Payroll\` and `Crmleaf\Payroll\Laravel\` are not
 * resolvable from vendor/autoload.php. This mirrors the fallback in
 * tests/bootstrap.php, and it lives in its own file rather than in TestCase
 * because PHPUnit resolves data providers before any test-case lifecycle hook
 * runs - a provider naming a package class needs the loader registered at the
 * moment the test class file is loaded, which is exactly when this is required.
 *
 * It becomes a no-op the day the root autoload block covers both namespaces.
 */

if (class_exists(\Crmleaf\Payroll\Laravel\PayrollServiceProvider::class, false)) {
    return;
}

$map = [
    'Crmleaf\\Payroll\\Laravel\\' => \dirname(__DIR__).'/src/',
    'Crmleaf\\Payroll\\' => \dirname(__DIR__, 2).'/payroll-core/src/',
];

spl_autoload_register(static function (string $class) use ($map): void {
    foreach ($map as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $file = $directory.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';

        if (is_file($file)) {
            require $file;

            return;
        }
    }
});
