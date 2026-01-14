<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Cart\DTOs\CartDTO;
use App\Domain\Cart\DTOs\CartItemDTO;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\ProductDTO;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\DTOs\CustomerDTO;
use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\DTOs\OrderDTO;
use App\Domain\Pricing\Services\DiscountCalculator;
use App\Domain\Pricing\Services\PriceCalculationService;
use App\Infrastructure\Events\OrderEventDispatcher;
use App\Infrastructure\Mail\OrderMailer;

/**
 * Order Application Service - Orchestrates the order workflow.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * function opslaan() {
 *     // SQL Injection
 *     $sql = "INSERT INTO orders VALUES (NULL, ". $this->klant_id. "...";
 *     mysql_query($sql) or die("DATABASE STUK: ". mysql_error());
 *
 *     // Side effect: email in save function
 *     @mail("admin@solvari.nl", $onderwerp, "Er is betaald!");
 *
 *     // Header after output
 *     echo "Opgeslagen!";
 *     header("Location: index.php?status=ok");
 * }
 * ```
 *
 * NOW: Clean orchestration with proper separation of concerns.
 */
final readonly class OrderApplicationService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private ProductRepositoryInterface $productRepository,
        private OrderRepositoryInterface $orderRepository,
        private PriceCalculationService $priceCalculator,
        private OrderMailer $mailer,
        private OrderEventDispatcher $eventDispatcher,
    ) {}

    /**
     * Get data needed for the order form.
     *
     * @return array{customer: CustomerDTO, products: list<ProductDTO>}
     */
    public function getOrderFormData(int $customerId): array
    {
        return [
            'customer' => $this->customerRepository->findById($customerId),
            'products' => $this->productRepository->findAllActive(),
        ];
    }

    /**
     * Create a new order.
     *
     * @param array<int, int> $cartItems Product ID => Quantity
     */
    public function createOrder(int $customerId, array $cartItems): OrderDTO
    {
        // Verify customer exists
        $customer = $this->customerRepository->findById($customerId);

        // Load product data
        $productIds = array_keys($cartItems);
        $products = $this->productRepository->findByIds($productIds);

        // Build cart DTO
        $items = [];
        foreach ($cartItems as $productId => $quantity) {
            if (!isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];
            $items[] = new CartItemDTO(
                productId: $product->id,
                productName: $product->name,
                unitPrice: $product->price,
                quantity: $quantity,
                vatCategory: $product->vatCategory,
            );
        }

        $cart = new CartDTO($items);

        if ($cart->isEmpty()) {
            throw new \InvalidArgumentException('Cannot create order with empty cart');
        }

        // Calculate prices
        $pricing = $this->priceCalculator->calculatePriceBreakdown($cart);

        // Create order DTO
        $order = OrderDTO::create(
            customerId: $customerId,
            cart: $cart,
            discountAmount: $pricing->discountAmount,
        );

        // Save to database
        $savedOrder = $this->orderRepository->save($order);

        // Dispatch event (async email, notifications, etc.)
        $this->eventDispatcher->dispatch(
            new \App\Domain\Order\Events\OrderCreatedEvent($savedOrder, $customer),
        );

        return $savedOrder;
    }

    /**
     * Delete an order.
     */
    public function deleteOrder(int $orderId, int $customerId): void
    {
        $this->orderRepository->delete($orderId, $customerId);

        $this->eventDispatcher->dispatch(
            new \App\Domain\Order\Events\OrderDeletedEvent($orderId, $customerId),
        );
    }
}
