<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

/**
 * BTW tarieven volgens Nederlandse wetgeving.
 */
enum VatRate: string
{
    case HIGH = 'high';      // 21% - Standaard tarief
    case LOW = 'low';        // 9% - Verlaagd tarief (was 6% tot 2019)
    case ZERO = 'zero';      // 0% - Vrijgesteld

    /**
     * Geef de decimale waarde van het BTW tarief.
     */
    public function rate(): float
    {
        return match ($this) {
            self::HIGH => 0.21,
            self::LOW => 0.09,
            self::ZERO => 0.0,
        };
    }

    /**
     * Bepaal BTW tarief op basis van product type.
     */
    public static function fromProductType(int $type): self
    {
        return match ($type) {
            1 => self::HIGH,
            2 => self::LOW,
            default => self::ZERO,
        };
    }
}
