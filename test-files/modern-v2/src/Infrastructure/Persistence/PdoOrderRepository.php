<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Cart\DTOs\CartDTO;
use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\DTOs\OrderDTO;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Exceptions\OrderNotDeletableException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use PDO;

/**
 * PDO-based Order Repository.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * // SQL Injection + serialize (unsafe deserialization attack)
 * $sql = "INSERT INTO orders VALUES (NULL, ". $this->klant_id. ", '".
 *         time(). "', '". serialize($_POST['mandje']). "')";
 * mysql_query($sql) or die("DATABASE STUK: ". mysql_error());
 *
 * // CSRF + SQL Injection in delete
 * mysql_query("DELETE FROM orders WHERE id = ". $_GET['oid']);
 * ```
 *
 * NOW: Prepared statements, JSON storage, proper authorization.
 */
final readonly class PdoOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function save(OrderDTO $order): OrderDTO
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (
                customer_id, status, items_json, subtotal, vat_total,
                discount_amount, grand_total, created_at
            ) VALUES (
                :customer_id, :status, :items_json, :subtotal, :vat_total,
                :discount_amount, :grand_total, :created_at
            )',
        );

        $stmt->execute([
            'customer_id' => $order->customerId,
            'status' => $order->status->value,
            'items_json' => json_encode(
                array_map(
                    static fn ($item) => [
                        'product_id' => $item->productId,
                        'product_name' => $item->productName,
                        'unit_price' => $item->unitPrice,
                        'quantity' => $item->quantity,
                        'vat_category' => $item->vatCategory->value,
                    ],
                    $order->cart->items,
                ),
                JSON_THROW_ON_ERROR,
            ),
            'subtotal' => $order->subtotal,
            'vat_total' => $order->vatTotal,
            'discount_amount' => $order->discountAmount,
            'grand_total' => $order->grandTotal,
            'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return new OrderDTO(
            id: $newId,
            customerId: $order->customerId,
            cart: $order->cart,
            status: $order->status,
            subtotal: $order->subtotal,
            vatTotal: $order->vatTotal,
            discountAmount: $order->discountAmount,
            grandTotal: $order->grandTotal,
            createdAt: $order->createdAt,
        );
    }

    public function findById(int $id): ?OrderDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, customer_id, status, items_json, subtotal, vat_total,
                    discount_amount, grand_total, created_at, updated_at
             FROM orders
             WHERE id = :id
             LIMIT 1',
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateOrder($row);
    }

    /**
     * Delete order with proper authorization check.
     */
    public function delete(int $id, int $customerId): void
    {
        // First, verify the order exists and belongs to the customer
        $stmt = $this->pdo->prepare(
            'SELECT id, customer_id, status FROM orders WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new OrderNotFoundException($id);
        }

        // Verify ownership (prevents unauthorized deletion)
        if ((int) $row['customer_id'] !== $customerId) {
            throw new OrderNotFoundException($id);
        }

        // Check if order can be deleted
        $status = OrderStatus::tryFrom((string) $row['status']) ?? OrderStatus::PENDING;
        if (!$status->isDeletable()) {
            throw new OrderNotDeletableException($id, $status);
        }

        // Safe delete with prepared statement
        $deleteStmt = $this->pdo->prepare(
            'DELETE FROM orders WHERE id = :id AND customer_id = :customer_id',
        );
        $deleteStmt->execute([
            'id' => $id,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * @return list<OrderDTO>
     */
    public function findByCustomerId(int $customerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, customer_id, status, items_json, subtotal, vat_total,
                    discount_amount, grand_total, created_at, updated_at
             FROM orders
             WHERE customer_id = :customer_id
             ORDER BY created_at DESC',
        );

        $stmt->execute(['customer_id' => $customerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn (array $row): OrderDTO => $this->hydrateOrder($row),
            $rows,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateOrder(array $row): OrderDTO
    {
        $itemsData = json_decode((string) $row['items_json'], true, 512, JSON_THROW_ON_ERROR);
        $cart = $this->hydrateCart($itemsData);

        return OrderDTO::fromDatabaseRow($row, $cart);
    }

    /**
     * @param array<int, array<string, mixed>> $itemsData
     */
    private function hydrateCart(array $itemsData): CartDTO
    {
        $items = array_map(
            static fn (array $item) => \App\Domain\Cart\DTOs\CartItemDTO::fromLegacyArray($item),
            $itemsData,
        );

        return new CartDTO($items);
    }
}
