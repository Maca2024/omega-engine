<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Order\Services\OrderCreatedEvent;
use App\Domain\Order\Services\OrderEventDispatcherInterface;

/**
 * Queue-based event dispatcher.
 *
 * In productie zou dit naar een queue gaan (Redis, RabbitMQ, etc.)
 * zodat de HTTP response niet wacht op email delivery.
 */
final readonly class QueuedOrderEventDispatcher implements OrderEventDispatcherInterface
{
    public function __construct(
        private OrderMailer $mailer,
    ) {}

    public function dispatch(OrderCreatedEvent $event): void
    {
        // In productie: dispatch naar queue
        // $this->queue->push(new SendOrderConfirmationJob($event));

        // Voor nu: direct versturen (kan later async worden)
        $this->mailer->sendOrderConfirmation($event->orderId, $event->user);
    }
}
