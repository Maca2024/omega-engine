<?php

declare(strict_types=1);

namespace App\Domain\Pricing\DTOs;

/**
 * Immutable price breakdown with all calculated values.
 */
final readonly class PriceBreakdownDTO
{
    /**
     * @param array<string, float> $vatBreakdown VAT amounts by category
     */
    public function __construct(
        public float $subtotal,
        public float $discountAmount,
        public float $discountedSubtotal,
        public array $vatBreakdown,
        public float $totalVat,
        public float $grandTotal,
    ) {}

    public static function zero(): self
    {
        return new self(
            subtotal: 0.0,
            discountAmount: 0.0,
            discountedSubtotal: 0.0,
            vatBreakdown: [],
            totalVat: 0.0,
            grandTotal: 0.0,
        );
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount > 0;
    }

    public function discountPercentage(): float
    {
        if ($this->subtotal <= 0) {
            return 0.0;
        }

        return round(($this->discountAmount / $this->subtotal) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'discounted_subtotal' => $this->discountedSubtotal,
            'vat_breakdown' => $this->vatBreakdown,
            'total_vat' => $this->totalVat,
            'grand_total' => $this->grandTotal,
        ];
    }
}
