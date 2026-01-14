<?php

/**
 * ==============================================================================
 * PEST CONFIGURATION
 * ==============================================================================
 *
 * Dit bestand configureert Pest voor de Omega Engine tests.
 *
 * @see https://pestphp.com/docs/configuring-pest
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific
| PHPUnit test case class. By default, that class is "PHPUnit\Framework\TestCase".
| Of course, you may need to change it using the "uses()" function to bind a
| different classes or traits to your test classes.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain
| conditions. The "expect()" function gives you access to a set of "expectations"
| methods that you can use to assert different things. Of course, you may extend
| the Expectation API at any time.
|
*/

expect()->extend('toHaveKeys', function (array $keys): void {
    foreach ($keys as $key) {
        expect($this->value)->toHaveKey($key);
    }
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code
| that you use frequently. Here you can define those functions, which are
| then available in all your test files.
|
*/

/**
 * Helper functie om een temporary file te maken voor tests.
 *
 * @param  string $content  De content voor de file
 * @return string           Het pad naar de temporary file
 */
function createTempPhpFile(string $content): string
{
    $tempFile = sys_get_temp_dir() . '/omega_test_' . uniqid() . '.php';
    file_put_contents($tempFile, $content);

    // Register cleanup
    register_shutdown_function(function () use ($tempFile): void {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    });

    return $tempFile;
}
