<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

use App\Domain\Cart\DTOs\CartDTO;
use App\Domain\Order\Enums\OrderStatus;
use DateTimeImmutable;

/**
 * Immutable Order Data Transfer Object.
 */
final readonly class OrderDTO
{
    public function __construct(
        public ?int $id,
        public int $customerId,
        public CartDTO $cart,
        public OrderStatus $status,
        public float $subtotal,
        public float $vatTotal,
        public float $discountAmount,
        public float $grandTotal,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        int $customerId,
        CartDTO $cart,
        float $discountAmount = 0.0,
    ): self {
        $subtotal = $cart->subtotal();
        $discountedSubtotal = $subtotal - $discountAmount;
        $vatTotal = $cart->totalVat();
        $grandTotal = $discountedSubtotal + $vatTotal;

        return new self(
            id: null,
            customerId: $customerId,
            cart: $cart,
            status: OrderStatus::PENDING,
            subtotal: round($subtotal, 2),
            vatTotal: round($vatTotal, 2),
            discountAmount: round($discountAmount, 2),
            grandTotal: round($grandTotal, 2),
            createdAt: new DateTimeImmutable(),
        );
    }

    /**
     * Create from database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row, CartDTO $cart): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            customerId: (int) ($row['klant_id'] ?? $row['customer_id'] ?? 0),
            cart: $cart,
            status: OrderStatus::tryFrom((string) ($row['status'] ?? '')) ?? OrderStatus::PENDING,
            subtotal: (float) ($row['subtotal'] ?? 0.0),
            vatTotal: (float) ($row['vat_total'] ?? $row['btw_totaal'] ?? 0.0),
            discountAmount: (float) ($row['discount'] ?? $row['korting'] ?? 0.0),
            grandTotal: (float) ($row['grand_total'] ?? $row['totaal'] ?? 0.0),
            createdAt: new DateTimeImmutable((string) ($row['created_at'] ?? 'now')),
            updatedAt: isset($row['updated_at'])
                ? new DateTimeImmutable((string) $row['updated_at'])
                : null,
        );
    }

    public function withStatus(OrderStatus $status): self
    {
        return new self(
            id: $this->id,
            customerId: $this->customerId,
            cart: $this->cart,
            status: $status,
            subtotal: $this->subtotal,
            vatTotal: $this->vatTotal,
            discountAmount: $this->discountAmount,
            grandTotal: $this->grandTotal,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            OrderStatus::PAID,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ], true);
    }
}
