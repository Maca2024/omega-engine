<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Customer\DTOs\CustomerDTO;
use App\Domain\Order\DTOs\OrderDTO;

/**
 * Order Mailer - Proper email handling.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * // @ suppresses errors (bad practice)
 * // Hardcoded email, no template, no error handling
 * @mail("admin@solvari.nl", $onderwerp, "Er is betaald!");
 * ```
 *
 * NOW: Proper email service with templates and error handling.
 */
final readonly class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
        private string $fromEmail,
        private string $fromName,
    ) {}

    public function sendOrderConfirmation(OrderDTO $order, CustomerDTO $customer): void
    {
        // Customer confirmation
        $this->mailer->send(
            to: $customer->email,
            subject: sprintf('Order Confirmation #%d', $order->id),
            template: 'emails/order-confirmation',
            data: [
                'customer' => $customer,
                'order' => $order,
            ],
        );

        // Admin notification
        $this->mailer->send(
            to: $this->adminEmail,
            subject: sprintf('New Order #%d from %s', $order->id, $customer->name),
            template: 'emails/admin-order-notification',
            data: [
                'customer' => $customer,
                'order' => $order,
            ],
        );
    }
}
