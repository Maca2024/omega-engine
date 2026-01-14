<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum OrderAction: string
{
    case VIEW = 'view';
    case SAVE = 'save';
    case DELETE = 'delete';

    public static function fromRequest(?string $action): self
    {
        return match ($action) {
            'save' => self::SAVE,
            'delete' => self::DELETE,
            default => self::VIEW,
        };
    }

    public function requiresAuthentication(): bool
    {
        return $this !== self::VIEW;
    }

    public function requiresCsrfToken(): bool
    {
        return $this === self::SAVE || $this === self::DELETE;
    }
}
