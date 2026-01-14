<?php

declare(strict_types=1);

namespace App\Domain\Order\Contracts;

use App\Domain\Order\DTOs\OrderDTO;
use App\Domain\Order\DTOs\UserDTO;

/**
 * Contract voor Order Repository.
 */
interface OrderRepositoryInterface
{
    public function findUserById(int $userId): ?UserDTO;

    public function createOrder(OrderDTO $order): int;

    public function deleteOrder(int $orderId, int $userId): bool;

    /**
     * @return array<array{id: int, name: string, price: float, stock: int, type: int}>
     */
    public function getAvailableProducts(): array;
}
