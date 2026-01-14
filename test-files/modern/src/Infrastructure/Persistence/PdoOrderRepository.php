<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\DTOs\OrderDTO;
use App\Domain\Order\DTOs\UserDTO;
use PDO;

/**
 * PDO implementatie van de Order Repository.
 *
 * Alle database interactie is geisoleerd in deze class.
 * Gebruikt prepared statements voor SQL injection preventie.
 */
final readonly class PdoOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findUserById(int $userId): ?UserDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, firstname, lastname FROM users WHERE id = :id'
        );

        $stmt->execute(['id' => $userId]);

        /** @var array{id: int, email: string, firstname: string, lastname: string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return UserDTO::fromArray($row);
    }

    public function createOrder(OrderDTO $order): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (user_id, created_at, data, status)
             VALUES (:user_id, :created_at, :data, :status)'
        );

        $stmt->execute([
            'user_id' => $order->userId,
            'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
            'data' => json_encode($order->items, JSON_THROW_ON_ERROR),
            'status' => $order->status->value,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteOrder(int $orderId, int $userId): bool
    {
        // Soft delete met ownership check voor security
        $stmt = $this->pdo->prepare(
            'UPDATE orders
             SET status = :status, deleted_at = NOW()
             WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );

        $stmt->execute([
            'status' => 'cancelled',
            'id' => $orderId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<array{id: int, name: string, price: float, stock: int, type: int}>
     */
    public function getAvailableProducts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, price, stock, type FROM products WHERE stock > 0'
        );

        if ($stmt === false) {
            return [];
        }

        /** @var array<array{id: int, name: string, price: float, stock: int, type: int}> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
