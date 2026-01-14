<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderCreatedEvent;
use App\Domain\Order\Events\OrderDeletedEvent;
use App\Infrastructure\Mail\OrderMailer;
use Psr\Log\LoggerInterface;

/**
 * Event dispatcher for order-related events.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * // Side-effect email buried in save function
 * @mail("admin@aetherlink.ai", $onderwerp, "Er is betaald!");
 * ```
 *
 * NOW: Clean event-driven architecture with async support.
 */
final readonly class OrderEventDispatcher
{
    public function __construct(
        private OrderMailer $mailer,
        private ?LoggerInterface $logger = null,
    ) {}

    public function dispatch(object $event): void
    {
        match (true) {
            $event instanceof OrderCreatedEvent => $this->handleOrderCreated($event),
            $event instanceof OrderDeletedEvent => $this->handleOrderDeleted($event),
            default => $this->logger?->warning('Unknown event type: ' . $event::class),
        };
    }

    private function handleOrderCreated(OrderCreatedEvent $event): void
    {
        $this->logger?->info('Order created', [
            'order_id' => $event->order->id,
            'customer_id' => $event->customer->id,
            'total' => $event->order->grandTotal,
        ]);

        // Send confirmation email (can be made async via queue)
        $this->mailer->sendOrderConfirmation($event->order, $event->customer);
    }

    private function handleOrderDeleted(OrderDeletedEvent $event): void
    {
        $this->logger?->info('Order deleted', [
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
        ]);
    }
}
