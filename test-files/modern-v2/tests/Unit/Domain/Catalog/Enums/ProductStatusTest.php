<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\ProductStatus;

describe('ProductStatus Enum', function (): void {

    it('reports ACTIVE as available', function (): void {
        expect(ProductStatus::ACTIVE->isAvailable())->toBeTrue();
    });

    it('reports INACTIVE as not available', function (): void {
        expect(ProductStatus::INACTIVE->isAvailable())->toBeFalse();
    });

    it('reports OUT_OF_STOCK as not available', function (): void {
        expect(ProductStatus::OUT_OF_STOCK->isAvailable())->toBeFalse();
    });

    it('reports DISCONTINUED as not available', function (): void {
        expect(ProductStatus::DISCONTINUED->isAvailable())->toBeFalse();
    });

    describe('fromLegacyFlag', function (): void {

        it('maps "J" to ACTIVE', function (): void {
            expect(ProductStatus::fromLegacyFlag('J'))->toBe(ProductStatus::ACTIVE);
        });

        it('maps "j" to ACTIVE (case insensitive)', function (): void {
            expect(ProductStatus::fromLegacyFlag('j'))->toBe(ProductStatus::ACTIVE);
        });

        it('maps "Y" to ACTIVE', function (): void {
            expect(ProductStatus::fromLegacyFlag('Y'))->toBe(ProductStatus::ACTIVE);
        });

        it('maps "1" to ACTIVE', function (): void {
            expect(ProductStatus::fromLegacyFlag('1'))->toBe(ProductStatus::ACTIVE);
        });

        it('maps "N" to INACTIVE', function (): void {
            expect(ProductStatus::fromLegacyFlag('N'))->toBe(ProductStatus::INACTIVE);
        });

        it('maps empty string to INACTIVE', function (): void {
            expect(ProductStatus::fromLegacyFlag(''))->toBe(ProductStatus::INACTIVE);
        });

    });

});
