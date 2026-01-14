<?php

declare(strict_types=1);

use App\Domain\Cart\DTOs\CartDTO;
use App\Domain\Cart\DTOs\CartItemDTO;
use App\Domain\Catalog\Enums\VatCategory;

describe('CartDTO', function (): void {

    it('reports empty cart correctly', function (): void {
        $cart = new CartDTO([]);

        expect($cart->isEmpty())->toBeTrue();
        expect($cart->itemCount())->toBe(0);
        expect($cart->totalQuantity())->toBe(0);
    });

    it('calculates item count correctly', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 10.0, 2, VatCategory::HIGH),
            new CartItemDTO(2, 'B', 20.0, 1, VatCategory::LOW),
        ]);

        expect($cart->isEmpty())->toBeFalse();
        expect($cart->itemCount())->toBe(2);
    });

    it('calculates total quantity correctly', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 10.0, 3, VatCategory::HIGH),
            new CartItemDTO(2, 'B', 20.0, 2, VatCategory::LOW),
        ]);

        expect($cart->totalQuantity())->toBe(5);
    });

    it('calculates subtotal correctly', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 100.0, 2, VatCategory::HIGH),  // 200
            new CartItemDTO(2, 'B', 50.0, 3, VatCategory::LOW),    // 150
        ]);

        expect($cart->subtotal())->toBe(350.0);
    });

    it('calculates total VAT correctly', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 100.0, 1, VatCategory::HIGH),  // VAT: 21
            new CartItemDTO(2, 'B', 100.0, 1, VatCategory::LOW),   // VAT: 9
            new CartItemDTO(3, 'C', 100.0, 1, VatCategory::ZERO),  // VAT: 0
        ]);

        expect($cart->totalVat())->toBe(30.0);
    });

    it('calculates grand total correctly', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 100.0, 1, VatCategory::HIGH),  // 100 + 21 = 121
        ]);

        expect($cart->grandTotal())->toBe(121.0);
    });

    it('provides VAT breakdown by category', function (): void {
        $cart = new CartDTO([
            new CartItemDTO(1, 'A', 100.0, 1, VatCategory::HIGH),  // VAT: 21
            new CartItemDTO(2, 'B', 200.0, 1, VatCategory::HIGH),  // VAT: 42
            new CartItemDTO(3, 'C', 100.0, 1, VatCategory::LOW),   // VAT: 9
        ]);

        $breakdown = $cart->vatBreakdown();

        expect($breakdown)->toHaveKey('high');
        expect($breakdown)->toHaveKey('low');
        expect($breakdown['high'])->toBe(63.0); // 21 + 42
        expect($breakdown['low'])->toBe(9.0);
    });

    describe('fromLegacyMandje', function (): void {

        it('creates cart from legacy mandje format', function (): void {
            $mandje = [
                1 => 2,  // Product 1, quantity 2
                2 => 3,  // Product 2, quantity 3
            ];

            $products = [
                1 => ['naam' => 'Product A', 'prijs' => 10.0, 'soort' => 1],
                2 => ['naam' => 'Product B', 'prijs' => 20.0, 'soort' => 2],
            ];

            $cart = CartDTO::fromLegacyMandje($mandje, $products);

            expect($cart->itemCount())->toBe(2);
            expect($cart->totalQuantity())->toBe(5);
        });

        it('skips items with zero quantity', function (): void {
            $mandje = [
                1 => 2,
                2 => 0,  // Zero quantity - should skip
            ];

            $products = [
                1 => ['naam' => 'A', 'prijs' => 10.0, 'soort' => 1],
                2 => ['naam' => 'B', 'prijs' => 20.0, 'soort' => 1],
            ];

            $cart = CartDTO::fromLegacyMandje($mandje, $products);

            expect($cart->itemCount())->toBe(1);
        });

        it('skips items not found in products', function (): void {
            $mandje = [
                1 => 2,
                999 => 5,  // Product doesn't exist
            ];

            $products = [
                1 => ['naam' => 'A', 'prijs' => 10.0, 'soort' => 1],
            ];

            $cart = CartDTO::fromLegacyMandje($mandje, $products);

            expect($cart->itemCount())->toBe(1);
        });

    });

});
