<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\VatCategory;

describe('VatCategory Enum', function (): void {

    it('returns correct rate for HIGH', function (): void {
        expect(VatCategory::HIGH->rate())->toBe(0.21);
    });

    it('returns correct rate for LOW', function (): void {
        expect(VatCategory::LOW->rate())->toBe(0.09);
    });

    it('returns correct rate for ZERO', function (): void {
        expect(VatCategory::ZERO->rate())->toBe(0.0);
    });

    it('returns correct percentage for HIGH', function (): void {
        expect(VatCategory::HIGH->percentage())->toBe(21);
    });

    it('returns correct percentage for LOW', function (): void {
        expect(VatCategory::LOW->percentage())->toBe(9);
    });

    it('returns correct percentage for ZERO', function (): void {
        expect(VatCategory::ZERO->percentage())->toBe(0);
    });

    describe('fromLegacyType', function (): void {

        it('maps integer 1 to HIGH', function (): void {
            expect(VatCategory::fromLegacyType(1))->toBe(VatCategory::HIGH);
        });

        it('maps string "1" to HIGH', function (): void {
            expect(VatCategory::fromLegacyType('1'))->toBe(VatCategory::HIGH);
        });

        it('maps string "laag" to LOW', function (): void {
            expect(VatCategory::fromLegacyType('laag'))->toBe(VatCategory::LOW);
        });

        it('maps integer 2 to LOW', function (): void {
            expect(VatCategory::fromLegacyType(2))->toBe(VatCategory::LOW);
        });

        it('maps unknown values to ZERO', function (): void {
            expect(VatCategory::fromLegacyType(99))->toBe(VatCategory::ZERO);
            expect(VatCategory::fromLegacyType(0))->toBe(VatCategory::ZERO);
            expect(VatCategory::fromLegacyType('unknown'))->toBe(VatCategory::ZERO);
        });

    });

});
