<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Order\DTOs\UserDTO;

/**
 * Order Mailer - Verantwoordelijk voor email verzending.
 */
final readonly class OrderMailer
{
    public function __construct(
        private string $fromAddress,
        private string $fromName,
    ) {}

    public function sendOrderConfirmation(int $orderId, UserDTO $user): bool
    {
        $to = $user->email;
        $subject = "Order bevestiging #{$orderId}";
        $body = $this->buildEmailBody($orderId, $user->firstName);

        $headers = implode("\r\n", [
            "From: {$this->fromName} <{$this->fromAddress}>",
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);

        return mail($to, $subject, $body, $headers);
    }

    private function buildEmailBody(int $orderId, string $userName): string
    {
        $escapedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Order Bevestiging</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Bedankt voor je order!</h1>
        <p>Beste {$escapedName},</p>
        <p>We hebben je order <strong>#{$orderId}</strong> ontvangen en gaan deze zo snel mogelijk verwerken.</p>
        <p>Je ontvangt een e-mail zodra je order is verzonden.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 14px;">
            Met vriendelijke groet,<br>
            AetherLink.AI Tech
        </p>
    </div>
</body>
</html>
HTML;
    }
}
