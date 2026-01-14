<?php

declare(strict_types=1);

use App\Domain\Order\Enums\VatRate;

describe('VatRate Enum', function (): void {

    it('returns correct rate for HIGH', function (): void {
        expect(VatRate::HIGH->rate())->toBe(0.21);
    });

    it('returns correct rate for LOW', function (): void {
        expect(VatRate::LOW->rate())->toBe(0.09);
    });

    it('returns correct rate for ZERO', function (): void {
        expect(VatRate::ZERO->rate())->toBe(0.0);
    });

    it('maps product type 1 to HIGH', function (): void {
        expect(VatRate::fromProductType(1))->toBe(VatRate::HIGH);
    });

    it('maps product type 2 to LOW', function (): void {
        expect(VatRate::fromProductType(2))->toBe(VatRate::LOW);
    });

    it('maps unknown product type to ZERO', function (): void {
        expect(VatRate::fromProductType(99))->toBe(VatRate::ZERO);
        expect(VatRate::fromProductType(0))->toBe(VatRate::ZERO);
    });

});
