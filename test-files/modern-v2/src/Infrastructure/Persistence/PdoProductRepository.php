<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\ProductDTO;
use PDO;

/**
 * PDO-based Product Repository.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * $res = mysql_query("SELECT * FROM producten WHERE actief = 'J'");
 * while ($row = mysql_fetch_object($res)) { ... }
 * ```
 *
 * NOW: Prepared statements with typed DTOs.
 */
final readonly class PdoProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findById(int $id): ?ProductDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, naam, prijs, voorraad, soort, actief, omschrijving, sku
             FROM producten
             WHERE id = :id
             LIMIT 1',
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ProductDTO::fromDatabaseRow($row);
    }

    /**
     * @return list<ProductDTO>
     */
    public function findAllActive(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, naam, prijs, voorraad, soort, actief, omschrijving, sku
             FROM producten
             WHERE actief = :active
             ORDER BY naam ASC',
        );

        $stmt->execute(['active' => 'J']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): ProductDTO => ProductDTO::fromDatabaseRow($row),
            $rows,
        );
    }

    /**
     * @param list<int> $ids
     * @return array<int, ProductDTO>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT id, naam, prijs, voorraad, soort, actief, omschrijving, sku
             FROM producten
             WHERE id IN ({$placeholders})",
        );

        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = [];
        foreach ($rows as $row) {
            $product = ProductDTO::fromDatabaseRow($row);
            $products[$product->id] = $product;
        }

        return $products;
    }
}
