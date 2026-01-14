<?php

declare(strict_types=1);

use App\Domain\Pricing\DTOs\PriceBreakdownDTO;

describe('PriceBreakdownDTO', function (): void {

    it('creates zero breakdown', function (): void {
        $breakdown = PriceBreakdownDTO::zero();

        expect($breakdown->subtotal)->toBe(0.0);
        expect($breakdown->discountAmount)->toBe(0.0);
        expect($breakdown->discountedSubtotal)->toBe(0.0);
        expect($breakdown->vatBreakdown)->toBe([]);
        expect($breakdown->totalVat)->toBe(0.0);
        expect($breakdown->grandTotal)->toBe(0.0);
    });

    it('reports hasDiscount correctly', function (): void {
        $withDiscount = new PriceBreakdownDTO(
            subtotal: 100.0,
            discountAmount: 10.0,
            discountedSubtotal: 90.0,
            vatBreakdown: [],
            totalVat: 0.0,
            grandTotal: 90.0,
        );

        $withoutDiscount = new PriceBreakdownDTO(
            subtotal: 100.0,
            discountAmount: 0.0,
            discountedSubtotal: 100.0,
            vatBreakdown: [],
            totalVat: 0.0,
            grandTotal: 100.0,
        );

        expect($withDiscount->hasDiscount())->toBeTrue();
        expect($withoutDiscount->hasDiscount())->toBeFalse();
    });

    it('calculates discount percentage correctly', function (): void {
        $breakdown = new PriceBreakdownDTO(
            subtotal: 200.0,
            discountAmount: 20.0,
            discountedSubtotal: 180.0,
            vatBreakdown: [],
            totalVat: 0.0,
            grandTotal: 180.0,
        );

        expect($breakdown->discountPercentage())->toBe(10.0);
    });

    it('returns zero discount percentage for zero subtotal', function (): void {
        $breakdown = PriceBreakdownDTO::zero();

        expect($breakdown->discountPercentage())->toBe(0.0);
    });

    it('converts to array correctly', function (): void {
        $breakdown = new PriceBreakdownDTO(
            subtotal: 100.0,
            discountAmount: 5.0,
            discountedSubtotal: 95.0,
            vatBreakdown: ['high' => 19.95],
            totalVat: 19.95,
            grandTotal: 114.95,
        );

        $array = $breakdown->toArray();

        expect($array)->toHaveKey('subtotal');
        expect($array)->toHaveKey('discount_amount');
        expect($array)->toHaveKey('vat_breakdown');
        expect($array['subtotal'])->toBe(100.0);
        expect($array['grand_total'])->toBe(114.95);
    });

});
