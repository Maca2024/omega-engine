<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\DTOs\CustomerDTO;
use App\Domain\Customer\Exceptions\CustomerNotFoundException;
use PDO;

/**
 * PDO-based Customer Repository.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * $q = mysql_query("SELECT * FROM klanten WHERE id = $kid"); // SQL INJECTION!
 * $this->klant_data = mysql_fetch_array($q);
 * ```
 *
 * NOW: Prepared statements with parameterized queries.
 */
final readonly class PdoCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findById(int $id): CustomerDTO
    {
        $customer = $this->findByIdOrNull($id);

        if ($customer === null) {
            throw new CustomerNotFoundException($id);
        }

        return $customer;
    }

    public function findByIdOrNull(int $id): ?CustomerDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, naam, email, telefoon, adres, created_at
             FROM klanten
             WHERE id = :id
             LIMIT 1',
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return CustomerDTO::fromDatabaseRow($row);
    }

    public function findByEmail(string $email): ?CustomerDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, naam, email, telefoon, adres, created_at
             FROM klanten
             WHERE email = :email
             LIMIT 1',
        );

        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return CustomerDTO::fromDatabaseRow($row);
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM klanten WHERE id = :id LIMIT 1',
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }
}
