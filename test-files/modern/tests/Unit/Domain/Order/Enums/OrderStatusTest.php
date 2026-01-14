<?php

declare(strict_types=1);

use App\Domain\Order\Enums\OrderStatus;

describe('OrderStatus Enum', function (): void {

    it('allows modification for PENDING status', function (): void {
        expect(OrderStatus::PENDING->isModifiable())->toBeTrue();
    });

    it('allows modification for CONFIRMED status', function (): void {
        expect(OrderStatus::CONFIRMED->isModifiable())->toBeTrue();
    });

    it('blocks modification for PROCESSING status', function (): void {
        expect(OrderStatus::PROCESSING->isModifiable())->toBeFalse();
    });

    it('blocks modification for SHIPPED status', function (): void {
        expect(OrderStatus::SHIPPED->isModifiable())->toBeFalse();
    });

    it('blocks modification for DELIVERED status', function (): void {
        expect(OrderStatus::DELIVERED->isModifiable())->toBeFalse();
    });

    it('blocks modification for CANCELLED status', function (): void {
        expect(OrderStatus::CANCELLED->isModifiable())->toBeFalse();
    });

    it('has correct string values', function (): void {
        expect(OrderStatus::PENDING->value)->toBe('pending');
        expect(OrderStatus::CONFIRMED->value)->toBe('confirmed');
        expect(OrderStatus::PROCESSING->value)->toBe('processing');
        expect(OrderStatus::SHIPPED->value)->toBe('shipped');
        expect(OrderStatus::DELIVERED->value)->toBe('delivered');
        expect(OrderStatus::CANCELLED->value)->toBe('cancelled');
    });

});
