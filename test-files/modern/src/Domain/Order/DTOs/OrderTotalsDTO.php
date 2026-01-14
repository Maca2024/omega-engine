<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

/**
 * Immutable DTO voor order totalen.
 */
final readonly class OrderTotalsDTO
{
    public function __construct(
        public float $subtotal,
        public float $vatHigh,
        public float $vatLow,
        public float $discountAmount,
        public float $grandTotal,
    ) {}

    /**
     * Bereken het totale BTW bedrag.
     */
    public function totalVat(): float
    {
        return $this->vatHigh + $this->vatLow;
    }

    /**
     * Factory method voor lege totalen.
     */
    public static function zero(): self
    {
        return new self(
            subtotal: 0.0,
            vatHigh: 0.0,
            vatLow: 0.0,
            discountAmount: 0.0,
            grandTotal: 0.0,
        );
    }
}
