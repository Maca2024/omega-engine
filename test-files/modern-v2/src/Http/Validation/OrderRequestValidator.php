<?php

declare(strict_types=1);

namespace App\Http\Validation;

use App\Domain\Order\Exceptions\ValidationException;

/**
 * Order Request Validator.
 *
 * FIXES TOXIC LEGACY CODE:
 * ```php
 * // NO VALIDATION AT ALL!
 * foreach($_REQUEST as $k => $v) { $k = $v; } // Register globals simulation
 * extract($_POST); // Direct variable injection
 * ```
 *
 * NOW: Explicit validation with type safety.
 */
final readonly class OrderRequestValidator
{
    /**
     * Validate create order request.
     *
     * @param array<string, mixed> $data
     * @return array{customer_id: int, cart: array<int, int>}
     * @throws ValidationException
     */
    public function validateCreateOrderRequest(array $data): array
    {
        $errors = [];

        // Validate customer ID
        if (!isset($data['customer_id'])) {
            $errors['customer_id'] = 'Customer ID is required';
        } elseif (!is_numeric($data['customer_id']) || (int) $data['customer_id'] <= 0) {
            $errors['customer_id'] = 'Invalid customer ID';
        }

        // Validate cart (mandje)
        $cart = $data['mandje'] ?? $data['cart'] ?? [];
        if (!is_array($cart)) {
            $errors['cart'] = 'Cart must be an array';
        } else {
            $validatedCart = $this->validateCart($cart);
            if ($validatedCart === []) {
                $errors['cart'] = 'Cart cannot be empty';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withErrors($errors);
        }

        return [
            'customer_id' => (int) $data['customer_id'],
            'cart' => $validatedCart ?? [],
        ];
    }

    /**
     * Validate delete order request.
     *
     * @param array<string, mixed> $data
     * @return array{order_id: int, customer_id: int}
     * @throws ValidationException
     */
    public function validateDeleteOrderRequest(array $data): array
    {
        $errors = [];

        if (!isset($data['order_id']) && !isset($data['oid'])) {
            $errors['order_id'] = 'Order ID is required';
        } else {
            $orderId = $data['order_id'] ?? $data['oid'];
            if (!is_numeric($orderId) || (int) $orderId <= 0) {
                $errors['order_id'] = 'Invalid order ID';
            }
        }

        if (!isset($data['customer_id'])) {
            $errors['customer_id'] = 'Customer ID is required';
        }

        if ($errors !== []) {
            throw ValidationException::withErrors($errors);
        }

        return [
            'order_id' => (int) ($data['order_id'] ?? $data['oid']),
            'customer_id' => (int) $data['customer_id'],
        ];
    }

    /**
     * Validate and sanitize cart items.
     *
     * @param array<int|string, int|string> $cart
     * @return array<int, int> Product ID => Quantity
     */
    private function validateCart(array $cart): array
    {
        $validated = [];

        foreach ($cart as $productId => $quantity) {
            $pid = filter_var($productId, FILTER_VALIDATE_INT);
            $qty = filter_var($quantity, FILTER_VALIDATE_INT);

            if ($pid === false || $pid <= 0) {
                continue;
            }

            if ($qty === false || $qty <= 0) {
                continue;
            }

            // Reasonable quantity limit
            $validated[$pid] = min($qty, 999);
        }

        return $validated;
    }
}
