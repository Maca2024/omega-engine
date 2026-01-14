<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\OrderApplicationService;
use App\Domain\Order\Enums\OrderAction;
use App\Domain\Order\Exceptions\OrderNotDeletableException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Exceptions\ValidationException;
use App\Http\Security\CsrfTokenManager;
use App\Http\Validation\OrderRequestValidator;
use App\Http\View\TemplateRenderer;

/**
 * Order Controller - Thin HTTP layer.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * class OrderProcessor {
 *     function doe_alles() { // "do everything" - god method
 *         global $actie;
 *         if ($actie == "save") {
 *             $this->opslaan();
 *         } elseif ($actie == "delete") {
 *             mysql_query("DELETE FROM orders WHERE id = ". $_GET['oid']); // SQL INJECTION!
 *             echo "<script>alert('Bestelling weggegooid!');</script>"; // XSS!
 *         }
 *     }
 * }
 * ```
 *
 * NOW: Clean separation of concerns, CSRF protection, proper responses.
 */
final readonly class OrderController
{
    public function __construct(
        private OrderApplicationService $orderService,
        private OrderRequestValidator $validator,
        private CsrfTokenManager $csrfManager,
        private TemplateRenderer $renderer,
    ) {}

    /**
     * @param array<string, mixed> $request
     */
    public function handle(array $request): string
    {
        $action = OrderAction::fromRequest($request['actie'] ?? $request['action'] ?? null);

        return match ($action) {
            OrderAction::SAVE => $this->handleSave($request),
            OrderAction::DELETE => $this->handleDelete($request),
            OrderAction::VIEW => $this->handleView($request),
        };
    }

    /**
     * @param array<string, mixed> $request
     */
    private function handleView(array $request): string
    {
        try {
            $customerId = (int) ($request['klant_id'] ?? $request['customer_id'] ?? 0);

            if ($customerId <= 0) {
                return $this->renderError('Customer ID is required');
            }

            $viewData = $this->orderService->getOrderFormData($customerId);

            return $this->renderer->render('order/form', [
                'customer' => $viewData['customer'],
                'products' => $viewData['products'],
                'csrf_token' => $this->csrfManager->getTokenField(),
            ]);
        } catch (\Exception $e) {
            return $this->renderError($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    private function handleSave(array $request): string
    {
        // CSRF Protection
        if (!$this->csrfManager->validateToken($request['_csrf_token'] ?? null)) {
            return $this->renderError('Invalid security token. Please refresh and try again.');
        }

        try {
            $validated = $this->validator->validateCreateOrderRequest($request);
            $order = $this->orderService->createOrder(
                customerId: $validated['customer_id'],
                cartItems: $validated['cart'],
            );

            // PRG Pattern: Redirect after POST
            $this->redirect('/orders/' . $order->id . '?status=success');

            return ''; // Never reached due to redirect
        } catch (ValidationException $e) {
            return $this->renderError(
                'Validation failed: ' . implode(', ', $e->getErrors()),
            );
        } catch (\Exception $e) {
            return $this->renderError('Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    private function handleDelete(array $request): string
    {
        // CSRF Protection
        if (!$this->csrfManager->validateToken($request['_csrf_token'] ?? null)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid security token',
            ], 403);
        }

        try {
            $validated = $this->validator->validateDeleteOrderRequest($request);
            $this->orderService->deleteOrder(
                orderId: $validated['order_id'],
                customerId: $validated['customer_id'],
            );

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);
        } catch (OrderNotFoundException) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        } catch (OrderNotDeletableException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (ValidationException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    private function renderError(string $message): string
    {
        return $this->renderer->render('error', [
            'message' => $message,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonResponse(array $data, int $statusCode = 200): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
