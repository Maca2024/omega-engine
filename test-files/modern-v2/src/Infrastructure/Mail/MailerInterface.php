<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

interface MailerInterface
{
    /**
     * Send an email.
     *
     * @param array<string, mixed> $data Template data
     */
    public function send(
        string $to,
        string $subject,
        string $template,
        array $data = [],
    ): void;
}
