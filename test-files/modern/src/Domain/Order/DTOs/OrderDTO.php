<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

use App\Domain\Order\Enums\OrderStatus;
use DateTimeImmutable;

/**
 * Immutable DTO voor een Order.
 */
final readonly class OrderDTO
{
    /**
     * @param array<CartItemDTO> $items
     */
    public function __construct(
        public ?int $id,
        public int $userId,
        public array $items,
        public OrderTotalsDTO $totals,
        public OrderStatus $status,
        public DateTimeImmutable $createdAt,
    ) {}
}
