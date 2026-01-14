<?php

declare(strict_types=1);

use App\Domain\Order\DTOs\CartItemDTO;
use App\Domain\Order\DTOs\OrderTotalsDTO;
use App\Domain\Order\Enums\VatRate;
use App\Domain\Order\Services\OrderCalculationService;

describe('OrderCalculationService', function (): void {

    beforeEach(function (): void {
        $this->service = new OrderCalculationService();
    });

    it('returns zero totals for empty cart', function (): void {
        $totals = $this->service->calculateTotals([]);

        expect($totals)->toBeInstanceOf(OrderTotalsDTO::class);
        expect($totals->subtotal)->toBe(0.0);
        expect($totals->grandTotal)->toBe(0.0);
    });

    it('calculates totals for single item without discount', function (): void {
        $items = [
            new CartItemDTO(1, 'Product', 50.00, 1, VatRate::HIGH),
        ];

        $totals = $this->service->calculateTotals($items);

        // 50 EUR, no discount (< 100), 21% VAT = 10.50
        expect($totals->subtotal)->toBe(50.00);
        expect($totals->vatHigh)->toBe(10.50);
        expect($totals->grandTotal)->toBe(60.50);
    });

    it('applies 5% discount for orders over 100 EUR', function (): void {
        $items = [
            new CartItemDTO(1, 'Product', 150.00, 1, VatRate::HIGH),
        ];

        $totals = $this->service->calculateTotals($items);

        // 150 EUR, 5% discount = 7.50, subtotal = 142.50
        // VAT 21% of 142.50 = 29.925
        expect($totals->subtotal)->toBe(142.50);
        expect($totals->discountAmount)->toBe(7.50);
    });

    it('applies 10% discount for orders over 500 EUR', function (): void {
        $items = [
            new CartItemDTO(1, 'Product', 600.00, 1, VatRate::HIGH),
        ];

        $totals = $this->service->calculateTotals($items);

        // 600 EUR, 10% discount = 60, subtotal = 540
        expect($totals->subtotal)->toBe(540.00);
        expect($totals->discountAmount)->toBe(60.00);
    });

    it('separates VAT by rate', function (): void {
        $items = [
            new CartItemDTO(1, 'High VAT Product', 50.00, 1, VatRate::HIGH),
            new CartItemDTO(2, 'Low VAT Product', 50.00, 1, VatRate::LOW),
        ];

        $totals = $this->service->calculateTotals($items);

        // No discount (each item < 100)
        // High VAT: 50 * 0.21 = 10.50
        // Low VAT: 50 * 0.09 = 4.50
        expect($totals->vatHigh)->toBe(10.50);
        expect($totals->vatLow)->toBe(4.50);
    });

    it('handles zero VAT products', function (): void {
        $items = [
            new CartItemDTO(1, 'Zero VAT Product', 100.00, 1, VatRate::ZERO),
        ];

        $totals = $this->service->calculateTotals($items);

        // 5% discount on 100 = 5, subtotal = 95
        // No VAT
        expect($totals->subtotal)->toBe(95.00);
        expect($totals->vatHigh)->toBe(0.0);
        expect($totals->vatLow)->toBe(0.0);
        expect($totals->grandTotal)->toBe(95.00);
    });

    it('calculates multiple items correctly', function (): void {
        $items = [
            new CartItemDTO(1, 'Product A', 30.00, 2, VatRate::HIGH),  // 60
            new CartItemDTO(2, 'Product B', 25.00, 4, VatRate::LOW),   // 100
            new CartItemDTO(3, 'Product C', 50.00, 1, VatRate::ZERO),  // 50
        ];

        $totals = $this->service->calculateTotals($items);

        // Item 1: 60 EUR, no discount, VAT high
        // Item 2: 100 EUR, no discount (not > 100), VAT low
        // Item 3: 50 EUR, no discount, no VAT
        // Subtotal: 60 + 100 + 50 = 210
        // VAT High: 60 * 0.21 = 12.60
        // VAT Low: 100 * 0.09 = 9.00
        expect($totals->subtotal)->toBe(210.00);
        expect($totals->vatHigh)->toBe(12.60);
        expect($totals->vatLow)->toBe(9.00);
        expect($totals->grandTotal)->toBe(231.60);
    });

});
