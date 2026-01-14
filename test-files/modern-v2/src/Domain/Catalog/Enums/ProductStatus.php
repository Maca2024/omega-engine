<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case OUT_OF_STOCK = 'out_of_stock';
    case DISCONTINUED = 'discontinued';

    public function isAvailable(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Map legacy 'J'/'N' active flags.
     */
    public static function fromLegacyFlag(string $flag): self
    {
        return match (strtoupper($flag)) {
            'J', 'Y', '1' => self::ACTIVE,
            default => self::INACTIVE,
        };
    }
}
