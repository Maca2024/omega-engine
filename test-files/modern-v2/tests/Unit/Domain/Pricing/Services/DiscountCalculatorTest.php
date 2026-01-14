<?php

declare(strict_types=1);

use App\Domain\Pricing\Rules\PercentageDiscountRule;
use App\Domain\Pricing\Rules\VolumeDiscountRule;
use App\Domain\Pricing\Services\DiscountCalculator;

describe('DiscountCalculator', function (): void {

    it('returns zero discount when no rules apply', function (): void {
        $calculator = new DiscountCalculator([
            new VolumeDiscountRule(threshold: 1000.0, percentage: 10.0),
        ]);

        $discount = $calculator->calculateDiscount(500.0);

        expect($discount)->toBe(0.0);
    });

    it('applies volume discount when threshold is met', function (): void {
        $calculator = new DiscountCalculator([
            new VolumeDiscountRule(threshold: 500.0, percentage: 10.0),
        ]);

        $discount = $calculator->calculateDiscount(600.0);

        expect($discount)->toBe(60.0); // 10% of 600
    });

    it('returns highest applicable discount when multiple rules match', function (): void {
        $calculator = new DiscountCalculator([
            new VolumeDiscountRule(threshold: 100.0, percentage: 5.0),   // 50
            new VolumeDiscountRule(threshold: 500.0, percentage: 10.0),  // 100
            new VolumeDiscountRule(threshold: 1000.0, percentage: 15.0), // N/A
        ]);

        $discount = $calculator->calculateDiscount(1000.0);

        expect($discount)->toBe(150.0); // Highest: 15% of 1000
    });

    it('uses default rules with withDefaultRules', function (): void {
        $calculator = DiscountCalculator::withDefaultRules();

        // 1000+ should get 10% discount
        expect($calculator->calculateDiscount(1000.0))->toBe(100.0);

        // 500+ should get 5% discount
        expect($calculator->calculateDiscount(600.0))->toBe(30.0);

        // 100+ should get 2% discount
        expect($calculator->calculateDiscount(150.0))->toBe(3.0);

        // Under 100 should get no discount
        expect($calculator->calculateDiscount(50.0))->toBe(0.0);
    });

    it('returns applicable rules', function (): void {
        $calculator = new DiscountCalculator([
            new VolumeDiscountRule(threshold: 100.0, percentage: 5.0),
            new VolumeDiscountRule(threshold: 500.0, percentage: 10.0),
        ]);

        $rules = $calculator->getApplicableRules(250.0);

        expect($rules)->toHaveCount(1);
    });

});

describe('VolumeDiscountRule', function (): void {

    it('applies when subtotal meets threshold', function (): void {
        $rule = new VolumeDiscountRule(threshold: 100.0, percentage: 10.0);

        expect($rule->applies(100.0))->toBeTrue();
        expect($rule->applies(150.0))->toBeTrue();
    });

    it('does not apply below threshold', function (): void {
        $rule = new VolumeDiscountRule(threshold: 100.0, percentage: 10.0);

        expect($rule->applies(99.99))->toBeFalse();
    });

    it('calculates discount correctly', function (): void {
        $rule = new VolumeDiscountRule(threshold: 100.0, percentage: 10.0);

        expect($rule->calculate(200.0))->toBe(20.0);
    });

    it('returns zero for non-applicable subtotal', function (): void {
        $rule = new VolumeDiscountRule(threshold: 100.0, percentage: 10.0);

        expect($rule->calculate(50.0))->toBe(0.0);
    });

    it('provides description', function (): void {
        $rule = new VolumeDiscountRule(threshold: 100.0, percentage: 10.0);

        expect($rule->getDescription())->toContain('10%');
        expect($rule->getDescription())->toContain('100.00');
    });

});

describe('PercentageDiscountRule', function (): void {

    it('always applies without minimum', function (): void {
        $rule = new PercentageDiscountRule(percentage: 10.0);

        expect($rule->applies(1.0))->toBeTrue();
    });

    it('respects minimum order value', function (): void {
        $rule = new PercentageDiscountRule(
            percentage: 10.0,
            minimumOrderValue: 50.0,
        );

        expect($rule->applies(49.0))->toBeFalse();
        expect($rule->applies(50.0))->toBeTrue();
    });

    it('respects maximum discount cap', function (): void {
        $rule = new PercentageDiscountRule(
            percentage: 50.0,
            maximumDiscount: 100.0,
        );

        // 50% of 500 = 250, but max is 100
        expect($rule->calculate(500.0))->toBe(100.0);

        // 50% of 100 = 50, under max
        expect($rule->calculate(100.0))->toBe(50.0);
    });

});
