<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Order\DTOs\CartItemDTO;
use App\Domain\Order\Exceptions\UnauthorizedOrderAccessException;
use App\Domain\Order\Exceptions\UserNotFoundException;
use App\Domain\Order\Exceptions\ValidationException;
use App\Domain\Order\Services\OrderService;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\DeleteOrderRequest;
use App\Http\Responses\JsonResponse;
use App\Http\Responses\ViewResponse;
use App\Http\Validation\RequestValidator;

/**
 * Order Controller - HTTP request handling.
 *
 * Verantwoordelijkheden:
 * - Input validatie
 * - Request routing
 * - Response formatting
 *
 * GEEN business logic - dat zit in de Services.
 */
final readonly class OrderController
{
    public function __construct(
        private OrderService $orderService,
        private RequestValidator $validator,
        private ViewResponse $view,
    ) {}

    /**
     * Route de request naar de juiste action.
     */
    public function handle(string $action, int $userId): JsonResponse|ViewResponse
    {
        return match ($action) {
            'save' => $this->save($userId),
            'delete' => $this->delete($userId),
            default => $this->showForm($userId),
        };
    }

    /**
     * Toon het order formulier.
     */
    public function showForm(int $userId): ViewResponse
    {
        $user = $this->orderService->getUser($userId);

        if ($user === null) {
            return $this->view->error('User niet gevonden', 404);
        }

        $products = $this->orderService->getAvailableProducts();

        return $this->view->render('order/form', [
            'user' => $user,
            'products' => $products,
        ]);
    }

    /**
     * Sla een nieuwe order op.
     */
    public function save(int $userId): JsonResponse
    {
        try {
            $request = $this->validator->validateCreateOrderRequest();

            $items = array_map(
                static fn(array $item): CartItemDTO => CartItemDTO::fromArray($item),
                $request->items
            );

            $order = $this->orderService->createOrder($userId, $items);

            return JsonResponse::success([
                'message' => 'Order succesvol aangemaakt',
                'order_id' => $order->id,
                'totals' => [
                    'subtotal' => $order->totals->subtotal,
                    'vat' => $order->totals->totalVat(),
                    'grand_total' => $order->totals->grandTotal,
                ],
            ]);
        } catch (ValidationException $e) {
            return JsonResponse::error(
                'Validatie gefaald: ' . json_encode($e->errors, JSON_THROW_ON_ERROR),
                422
            );
        } catch (UserNotFoundException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }

    /**
     * Verwijder een order.
     */
    public function delete(int $userId): JsonResponse
    {
        try {
            $request = $this->validator->validateDeleteOrderRequest();

            $this->orderService->deleteOrder($request->orderId, $userId);

            return JsonResponse::success([
                'message' => 'Order succesvol verwijderd',
            ]);
        } catch (ValidationException $e) {
            return JsonResponse::error(
                'Validatie gefaald: ' . json_encode($e->errors, JSON_THROW_ON_ERROR),
                422
            );
        } catch (UnauthorizedOrderAccessException $e) {
            return JsonResponse::error($e->getMessage(), 403);
        }
    }
}
