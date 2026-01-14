<?php

declare(strict_types=1);

use App\Domain\Order\Enums\OrderStatus;

describe('OrderStatus Enum', function (): void {

    describe('isModifiable', function (): void {

        it('allows modification for PENDING', function (): void {
            expect(OrderStatus::PENDING->isModifiable())->toBeTrue();
        });

        it('allows modification for CONFIRMED', function (): void {
            expect(OrderStatus::CONFIRMED->isModifiable())->toBeTrue();
        });

        it('blocks modification for PAID', function (): void {
            expect(OrderStatus::PAID->isModifiable())->toBeFalse();
        });

        it('blocks modification for PROCESSING', function (): void {
            expect(OrderStatus::PROCESSING->isModifiable())->toBeFalse();
        });

        it('blocks modification for SHIPPED', function (): void {
            expect(OrderStatus::SHIPPED->isModifiable())->toBeFalse();
        });

        it('blocks modification for DELIVERED', function (): void {
            expect(OrderStatus::DELIVERED->isModifiable())->toBeFalse();
        });

        it('blocks modification for CANCELLED', function (): void {
            expect(OrderStatus::CANCELLED->isModifiable())->toBeFalse();
        });

    });

    describe('isDeletable', function (): void {

        it('allows deletion for PENDING only', function (): void {
            expect(OrderStatus::PENDING->isDeletable())->toBeTrue();
            expect(OrderStatus::CONFIRMED->isDeletable())->toBeFalse();
            expect(OrderStatus::PAID->isDeletable())->toBeFalse();
            expect(OrderStatus::PROCESSING->isDeletable())->toBeFalse();
            expect(OrderStatus::SHIPPED->isDeletable())->toBeFalse();
            expect(OrderStatus::DELIVERED->isDeletable())->toBeFalse();
            expect(OrderStatus::CANCELLED->isDeletable())->toBeFalse();
        });

    });

    describe('canTransitionTo', function (): void {

        it('allows PENDING to CONFIRMED', function (): void {
            expect(OrderStatus::PENDING->canTransitionTo(OrderStatus::CONFIRMED))->toBeTrue();
        });

        it('allows PENDING to CANCELLED', function (): void {
            expect(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED))->toBeTrue();
        });

        it('blocks PENDING to SHIPPED directly', function (): void {
            expect(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED))->toBeFalse();
        });

        it('allows CONFIRMED to PAID', function (): void {
            expect(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::PAID))->toBeTrue();
        });

        it('allows PAID to PROCESSING', function (): void {
            expect(OrderStatus::PAID->canTransitionTo(OrderStatus::PROCESSING))->toBeTrue();
        });

        it('allows SHIPPED to DELIVERED', function (): void {
            expect(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::DELIVERED))->toBeTrue();
        });

        it('blocks transitions from DELIVERED', function (): void {
            expect(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED))->toBeFalse();
        });

        it('blocks transitions from CANCELLED', function (): void {
            expect(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PENDING))->toBeFalse();
        });

    });

});
