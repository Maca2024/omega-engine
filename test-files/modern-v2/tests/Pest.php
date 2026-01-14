<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidPrice', function () {
    return $this->toBeFloat()->toBeGreaterThanOrEqual(0.0);
});

expect()->extend('toBeValidPercentage', function () {
    return $this->toBeFloat()->toBeBetween(0.0, 100.0);
});
