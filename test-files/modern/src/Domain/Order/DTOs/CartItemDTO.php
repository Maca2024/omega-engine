<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

use App\Domain\Order\Enums\VatRate;

/**
 * Immutable DTO voor een cart item.
 */
final readonly class CartItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $price,
        public int $quantity,
        public VatRate $vatRate,
    ) {}

    /**
     * Bereken het subtotaal voor dit item.
     */
    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Bereken het BTW bedrag voor dit item.
     */
    public function vatAmount(): float
    {
        return $this->subtotal() * $this->vatRate->rate();
    }

    /**
     * Bereken het totaal inclusief BTW.
     */
    public function totalIncludingVat(): float
    {
        return $this->subtotal() + $this->vatAmount();
    }

    /**
     * Factory method van array data.
     *
     * @param array{id: int, name?: string, price: float, qty: int, type?: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? '',
            price: (float) $data['price'],
            quantity: (int) $data['qty'],
            vatRate: VatRate::fromProductType((int) ($data['type'] ?? 0)),
        );
    }
}
