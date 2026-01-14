<?php

declare(strict_types=1);

namespace App\Domain\Cart\DTOs;

use App\Domain\Catalog\Enums\VatCategory;

/**
 * Immutable Cart Data Transfer Object containing all cart items.
 */
final readonly class CartDTO
{
    /**
     * @param list<CartItemDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * Create from legacy POST mandje format.
     *
     * @param array<int, int|string> $mandje Product ID => Quantity mapping
     * @param array<int, array<string, mixed>> $products Product data indexed by ID
     */
    public static function fromLegacyMandje(array $mandje, array $products): self
    {
        $items = [];

        foreach ($mandje as $productId => $quantity) {
            $qty = (int) $quantity;
            if ($qty <= 0) {
                continue;
            }

            $productId = (int) $productId;
            if (!isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];
            $items[] = new CartItemDTO(
                productId: $productId,
                productName: (string) ($product['naam'] ?? ''),
                unitPrice: (float) ($product['prijs'] ?? 0.0),
                quantity: $qty,
                vatCategory: VatCategory::fromLegacyType($product['soort'] ?? 0),
            );
        }

        return new self($items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function totalQuantity(): int
    {
        return array_sum(array_map(
            static fn (CartItemDTO $item): int => $item->quantity,
            $this->items,
        ));
    }

    public function subtotal(): float
    {
        return array_sum(array_map(
            static fn (CartItemDTO $item): float => $item->subtotal(),
            $this->items,
        ));
    }

    public function totalVat(): float
    {
        return array_sum(array_map(
            static fn (CartItemDTO $item): float => $item->vatAmount(),
            $this->items,
        ));
    }

    public function grandTotal(): float
    {
        return round($this->subtotal() + $this->totalVat(), 2);
    }

    /**
     * @return array<string, float> VAT totals by category
     */
    public function vatBreakdown(): array
    {
        $breakdown = [];

        foreach ($this->items as $item) {
            $category = $item->vatCategory->value;
            $breakdown[$category] = ($breakdown[$category] ?? 0.0) + $item->vatAmount();
        }

        return $breakdown;
    }
}
