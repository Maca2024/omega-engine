<?php

declare(strict_types=1);

namespace App\Http\Validation;

use App\Domain\Order\Exceptions\ValidationException;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\DeleteOrderRequest;

/**
 * Request validator voor HTTP input.
 */
final class RequestValidator
{
    /**
     * Validate CreateOrderRequest.
     *
     * @throws ValidationException
     */
    public function validateCreateOrderRequest(): CreateOrderRequest
    {
        return $this->validateCreateOrder();
    }

    /**
     * Validate DeleteOrderRequest.
     *
     * @throws ValidationException
     */
    public function validateDeleteOrderRequest(): DeleteOrderRequest
    {
        return $this->validateDeleteOrder();
    }

    private function validateCreateOrder(): CreateOrderRequest
    {
        /** @var mixed $rawItems */
        $rawItems = $_POST['items'] ?? [];

        /** @var array<string, array<string>> $errors */
        $errors = [];

        if (!is_array($rawItems) || $rawItems === []) {
            $errors['items'] = ['Items zijn verplicht'];
            throw new ValidationException($errors);
        }

        /** @var array<int|string, mixed> $items */
        $items = $rawItems;

        /** @var array<array{id: int, price: float, qty: int, type: int}> $validatedItems */
        $validatedItems = [];

        foreach ($items as $index => $item) {
            $indexStr = (string) $index;

            if (!is_array($item)) {
                $errors["items.{$indexStr}"] = ['Item moet een array zijn'];
                continue;
            }

            /** @var array<string, mixed> $itemArray */
            $itemArray = $item;

            $qtyRaw = $itemArray['qty'] ?? 0;
            $qty = is_numeric($qtyRaw) ? (int) $qtyRaw : 0;

            // Skip items met qty 0
            if ($qty < 1) {
                continue;
            }

            $id = filter_var($itemArray['id'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($itemArray['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $type = filter_var($itemArray['type'] ?? 0, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                $errors["items.{$indexStr}.id"] = ['Ongeldig product ID'];
            }

            if ($price === false || $price < 0) {
                $errors["items.{$indexStr}.price"] = ['Ongeldige prijs'];
            }

            if ($id !== false && $price !== false && $type !== false) {
                $validatedItems[] = [
                    'id' => $id,
                    'price' => $price,
                    'qty' => $qty,
                    'type' => $type,
                ];
            }
        }

        if ($validatedItems === []) {
            $errors['items'] = ['Selecteer minimaal 1 product'];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new CreateOrderRequest($validatedItems);
    }

    private function validateDeleteOrder(): DeleteOrderRequest
    {
        $orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($orderId === false || $orderId === null || $orderId < 1) {
            throw new ValidationException(['order_id' => ['Ongeldig order ID']]);
        }

        return new DeleteOrderRequest($orderId);
    }
}
