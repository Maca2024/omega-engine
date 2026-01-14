<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * VAT categories following Dutch tax law.
 * - HIGH: 21% (standard rate)
 * - LOW: 9% (reduced rate for essentials)
 * - ZERO: 0% (exempt items)
 */
enum VatCategory: string
{
    case HIGH = 'high';
    case LOW = 'low';
    case ZERO = 'zero';

    public function rate(): float
    {
        return match ($this) {
            self::HIGH => 0.21,
            self::LOW => 0.09,
            self::ZERO => 0.0,
        };
    }

    public function percentage(): int
    {
        return (int) ($this->rate() * 100);
    }

    /**
     * Map legacy product type codes to VAT categories.
     * Legacy: 1 or "1" = HIGH, "laag" = LOW, else = ZERO
     */
    public static function fromLegacyType(int|string $type): self
    {
        if ($type === 1 || $type === '1') {
            return self::HIGH;
        }

        if ($type === 'laag' || $type === 2) {
            return self::LOW;
        }

        return self::ZERO;
    }
}
