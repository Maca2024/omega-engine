<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\DTOs\CartItemDTO;
use App\Domain\Order\DTOs\OrderDTO;
use App\Domain\Order\DTOs\UserDTO;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Exceptions\UnauthorizedOrderAccessException;
use App\Domain\Order\Exceptions\UserNotFoundException;
use DateTimeImmutable;

/**
 * Order Service - orchestreert order operaties.
 *
 * Bevat GEEN side effects zoals email versturen.
 * Events worden gedispatched voor async verwerking.
 */
final readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private OrderCalculationService $calculator,
        private OrderEventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Maak een nieuwe order aan.
     *
     * @param array<CartItemDTO> $items
     * @return OrderDTO De aangemaakte order met ID
     * @throws UserNotFoundException Als de user niet bestaat
     */
    public function createOrder(int $userId, array $items): OrderDTO
    {
        $user = $this->repository->findUserById($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($userId);
        }

        $totals = $this->calculator->calculateTotals($items);

        $order = new OrderDTO(
            id: null,
            userId: $userId,
            items: $items,
            totals: $totals,
            status: OrderStatus::PENDING,
            createdAt: new DateTimeImmutable(),
        );

        $orderId = $this->repository->createOrder($order);

        // Dispatch event voor async email versturen
        $this->eventDispatcher->dispatch(
            new OrderCreatedEvent($orderId, $user)
        );

        return new OrderDTO(
            id: $orderId,
            userId: $order->userId,
            items: $order->items,
            totals: $order->totals,
            status: $order->status,
            createdAt: $order->createdAt,
        );
    }

    /**
     * Verwijder een order (soft delete).
     *
     * @throws UnauthorizedOrderAccessException Als user geen eigenaar is
     */
    public function deleteOrder(int $orderId, int $userId): bool
    {
        $deleted = $this->repository->deleteOrder($orderId, $userId);

        if (!$deleted) {
            throw UnauthorizedOrderAccessException::forOrder($orderId);
        }

        return true;
    }

    public function getUser(int $userId): ?UserDTO
    {
        return $this->repository->findUserById($userId);
    }

    /**
     * @return array<array{id: int, name: string, price: float, stock: int, type: int}>
     */
    public function getAvailableProducts(): array
    {
        return $this->repository->getAvailableProducts();
    }
}
