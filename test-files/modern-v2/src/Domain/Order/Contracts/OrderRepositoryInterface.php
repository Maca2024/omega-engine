<?php

declare(strict_types=1);

namespace App\Domain\Order\Contracts;

use App\Domain\Order\DTOs\OrderDTO;

interface OrderRepositoryInterface
{
    /**
     * Save a new order.
     */
    public function save(OrderDTO $order): OrderDTO;

    /**
     * Find order by ID.
     */
    public function findById(int $id): ?OrderDTO;

    /**
     * Delete order by ID.
     * Only pending orders can be deleted.
     *
     * @throws OrderNotFoundException
     * @throws OrderNotDeletableException
     */
    public function delete(int $id, int $customerId): void;

    /**
     * Find orders for customer.
     *
     * @return list<OrderDTO>
     */
    public function findByCustomerId(int $customerId): array;
}
