<?php

declare(strict_types=1);

namespace App\Domain\Cart\DTOs;

use App\Domain\Catalog\Enums\VatCategory;

/**
 * Immutable Cart Item Data Transfer Object.
 */
final readonly class CartItemDTO
{
    public function __construct(
        public int $productId,
        public string $productName,
        public float $unitPrice,
        public int $quantity,
        public VatCategory $vatCategory,
    ) {}

    /**
     * Create from legacy cart array format.
     *
     * @param array<string, mixed> $item
     */
    public static function fromLegacyArray(array $item): self
    {
        return new self(
            productId: (int) ($item['id'] ?? $item['product_id'] ?? 0),
            productName: (string) ($item['naam'] ?? $item['name'] ?? ''),
            unitPrice: (float) ($item['prijs'] ?? $item['price'] ?? 0.0),
            quantity: max(1, (int) ($item['aantal'] ?? $item['quantity'] ?? 1)),
            vatCategory: VatCategory::fromLegacyType($item['soort'] ?? $item['type'] ?? 0),
        );
    }

    public function subtotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    public function vatAmount(): float
    {
        return round($this->subtotal() * $this->vatCategory->rate(), 2);
    }

    public function total(): float
    {
        return round($this->subtotal() + $this->vatAmount(), 2);
    }

    public function withQuantity(int $quantity): self
    {
        return new self(
            productId: $this->productId,
            productName: $this->productName,
            unitPrice: $this->unitPrice,
            quantity: max(1, $quantity),
            vatCategory: $this->vatCategory,
        );
    }
}
