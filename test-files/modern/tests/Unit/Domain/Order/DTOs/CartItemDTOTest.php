<?php

declare(strict_types=1);

use App\Domain\Order\DTOs\CartItemDTO;
use App\Domain\Order\Enums\VatRate;

describe('CartItemDTO', function (): void {

    it('calculates subtotal correctly', function (): void {
        $item = new CartItemDTO(
            id: 1,
            name: 'Test Product',
            price: 10.00,
            quantity: 3,
            vatRate: VatRate::HIGH,
        );

        expect($item->subtotal())->toBe(30.00);
    });

    it('calculates VAT amount correctly for HIGH rate', function (): void {
        $item = new CartItemDTO(
            id: 1,
            name: 'Test Product',
            price: 100.00,
            quantity: 1,
            vatRate: VatRate::HIGH,
        );

        expect($item->vatAmount())->toBe(21.00);
    });

    it('calculates VAT amount correctly for LOW rate', function (): void {
        $item = new CartItemDTO(
            id: 1,
            name: 'Test Product',
            price: 100.00,
            quantity: 1,
            vatRate: VatRate::LOW,
        );

        expect($item->vatAmount())->toBe(9.00);
    });

    it('calculates VAT amount as zero for ZERO rate', function (): void {
        $item = new CartItemDTO(
            id: 1,
            name: 'Test Product',
            price: 100.00,
            quantity: 1,
            vatRate: VatRate::ZERO,
        );

        expect($item->vatAmount())->toBe(0.00);
    });

    it('calculates total including VAT correctly', function (): void {
        $item = new CartItemDTO(
            id: 1,
            name: 'Test Product',
            price: 100.00,
            quantity: 2,
            vatRate: VatRate::HIGH,
        );

        // Subtotal: 200, VAT: 42, Total: 242
        expect($item->totalIncludingVat())->toBe(242.00);
    });

    it('creates from array correctly', function (): void {
        $item = CartItemDTO::fromArray([
            'id' => 5,
            'name' => 'Array Product',
            'price' => 25.50,
            'qty' => 4,
            'type' => 1,
        ]);

        expect($item->id)->toBe(5);
        expect($item->name)->toBe('Array Product');
        expect($item->price)->toBe(25.50);
        expect($item->quantity)->toBe(4);
        expect($item->vatRate)->toBe(VatRate::HIGH);
    });

    it('handles missing optional fields in fromArray', function (): void {
        $item = CartItemDTO::fromArray([
            'id' => 1,
            'price' => 10.00,
            'qty' => 1,
        ]);

        expect($item->name)->toBe('');
        expect($item->vatRate)->toBe(VatRate::ZERO);
    });

});
