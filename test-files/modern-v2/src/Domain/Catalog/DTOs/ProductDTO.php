<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\VatCategory;

/**
 * Immutable Product Data Transfer Object.
 */
final readonly class ProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $price,
        public int $stock,
        public VatCategory $vatCategory,
        public ProductStatus $status,
        public ?string $description = null,
        public ?string $sku = null,
    ) {}

    /**
     * Create from database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['naam'] ?? $row['name'] ?? ''),
            price: (float) ($row['prijs'] ?? $row['price'] ?? 0.0),
            stock: (int) ($row['voorraad'] ?? $row['stock'] ?? 0),
            vatCategory: VatCategory::fromLegacyType($row['soort'] ?? $row['type'] ?? 0),
            status: ProductStatus::fromLegacyFlag((string) ($row['actief'] ?? $row['active'] ?? 'N')),
            description: isset($row['omschrijving']) ? (string) $row['omschrijving'] : null,
            sku: isset($row['sku']) ? (string) $row['sku'] : null,
        );
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->stock < $threshold;
    }

    public function isAvailable(): bool
    {
        return $this->status->isAvailable() && $this->stock > 0;
    }

    public function getPriceWithVat(): float
    {
        return round($this->price * (1 + $this->vatCategory->rate()), 2);
    }

    public function getDisplayName(): string
    {
        return htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
    }
}
