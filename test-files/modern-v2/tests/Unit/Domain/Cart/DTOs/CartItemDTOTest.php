<?php

declare(strict_types=1);

use App\Domain\Cart\DTOs\CartItemDTO;
use App\Domain\Catalog\Enums\VatCategory;

describe('CartItemDTO', function (): void {

    it('calculates subtotal correctly', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test Product',
            unitPrice: 25.00,
            quantity: 4,
            vatCategory: VatCategory::HIGH,
        );

        expect($item->subtotal())->toBe(100.00);
    });

    it('calculates VAT amount for HIGH rate', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test Product',
            unitPrice: 100.00,
            quantity: 1,
            vatCategory: VatCategory::HIGH,
        );

        expect($item->vatAmount())->toBe(21.00);
    });

    it('calculates VAT amount for LOW rate', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test Product',
            unitPrice: 100.00,
            quantity: 1,
            vatCategory: VatCategory::LOW,
        );

        expect($item->vatAmount())->toBe(9.00);
    });

    it('calculates VAT amount as zero for ZERO rate', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test Product',
            unitPrice: 100.00,
            quantity: 1,
            vatCategory: VatCategory::ZERO,
        );

        expect($item->vatAmount())->toBe(0.00);
    });

    it('calculates total including VAT', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test Product',
            unitPrice: 100.00,
            quantity: 2,
            vatCategory: VatCategory::HIGH,
        );

        // Subtotal: 200, VAT: 42, Total: 242
        expect($item->total())->toBe(242.00);
    });

    it('creates with quantity from withQuantity', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test',
            unitPrice: 10.00,
            quantity: 1,
            vatCategory: VatCategory::HIGH,
        );

        $newItem = $item->withQuantity(5);

        expect($newItem->quantity)->toBe(5);
        expect($newItem->productId)->toBe(1);
        expect($item->quantity)->toBe(1); // Original unchanged (immutable)
    });

    it('enforces minimum quantity of 1 in withQuantity', function (): void {
        $item = new CartItemDTO(
            productId: 1,
            productName: 'Test',
            unitPrice: 10.00,
            quantity: 5,
            vatCategory: VatCategory::HIGH,
        );

        $newItem = $item->withQuantity(0);

        expect($newItem->quantity)->toBe(1);
    });

    describe('fromLegacyArray', function (): void {

        it('creates from legacy format with Dutch keys', function (): void {
            $item = CartItemDTO::fromLegacyArray([
                'id' => 42,
                'naam' => 'Legacy Product',
                'prijs' => 29.95,
                'aantal' => 3,
                'soort' => 1,
            ]);

            expect($item->productId)->toBe(42);
            expect($item->productName)->toBe('Legacy Product');
            expect($item->unitPrice)->toBe(29.95);
            expect($item->quantity)->toBe(3);
            expect($item->vatCategory)->toBe(VatCategory::HIGH);
        });

        it('creates from legacy format with English keys', function (): void {
            $item = CartItemDTO::fromLegacyArray([
                'product_id' => 42,
                'name' => 'Modern Product',
                'price' => 29.95,
                'quantity' => 3,
                'type' => 2,
            ]);

            expect($item->productId)->toBe(42);
            expect($item->productName)->toBe('Modern Product');
            expect($item->vatCategory)->toBe(VatCategory::LOW);
        });

        it('enforces minimum quantity of 1', function (): void {
            $item = CartItemDTO::fromLegacyArray([
                'id' => 1,
                'aantal' => 0,
            ]);

            expect($item->quantity)->toBe(1);
        });

    });

});
