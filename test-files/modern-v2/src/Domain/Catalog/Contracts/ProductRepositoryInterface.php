<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\DTOs\ProductDTO;

interface ProductRepositoryInterface
{
    /**
     * Find product by ID.
     */
    public function findById(int $id): ?ProductDTO;

    /**
     * Get all active products.
     *
     * @return list<ProductDTO>
     */
    public function findAllActive(): array;

    /**
     * Get products by IDs.
     *
     * @param list<int> $ids
     * @return array<int, ProductDTO> Indexed by product ID
     */
    public function findByIds(array $ids): array;
}
