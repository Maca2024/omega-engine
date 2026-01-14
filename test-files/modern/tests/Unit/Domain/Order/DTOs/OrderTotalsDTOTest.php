<?php

declare(strict_types=1);

use App\Domain\Order\DTOs\OrderTotalsDTO;

describe('OrderTotalsDTO', function (): void {

    it('calculates total VAT correctly', function (): void {
        $totals = new OrderTotalsDTO(
            subtotal: 100.00,
            vatHigh: 21.00,
            vatLow: 9.00,
            discountAmount: 5.00,
            grandTotal: 130.00,
        );

        expect($totals->totalVat())->toBe(30.00);
    });

    it('returns zero totals from factory method', function (): void {
        $totals = OrderTotalsDTO::zero();

        expect($totals->subtotal)->toBe(0.0);
        expect($totals->vatHigh)->toBe(0.0);
        expect($totals->vatLow)->toBe(0.0);
        expect($totals->discountAmount)->toBe(0.0);
        expect($totals->grandTotal)->toBe(0.0);
        expect($totals->totalVat())->toBe(0.0);
    });

    it('is immutable (readonly)', function (): void {
        $totals = new OrderTotalsDTO(
            subtotal: 100.00,
            vatHigh: 21.00,
            vatLow: 0.00,
            discountAmount: 0.00,
            grandTotal: 121.00,
        );

        // This should not be possible due to readonly
        expect(fn() => $totals->subtotal = 200.00)->toThrow(Error::class);
    });

});
