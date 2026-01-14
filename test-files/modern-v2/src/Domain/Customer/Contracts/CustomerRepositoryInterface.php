<?php

declare(strict_types=1);

namespace App\Domain\Customer\Contracts;

use App\Domain\Customer\DTOs\CustomerDTO;

interface CustomerRepositoryInterface
{
    /**
     * Find customer by ID.
     *
     * @throws CustomerNotFoundException
     */
    public function findById(int $id): CustomerDTO;

    /**
     * Find customer by ID, returns null if not found.
     */
    public function findByIdOrNull(int $id): ?CustomerDTO;

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?CustomerDTO;

    /**
     * Check if customer exists.
     */
    public function exists(int $id): bool;
}
