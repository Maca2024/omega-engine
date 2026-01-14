<?php

declare(strict_types=1);

namespace App\Domain\Customer\DTOs;

use DateTimeImmutable;

/**
 * Immutable Customer Data Transfer Object.
 */
final readonly class CustomerDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?AddressDTO $address = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {}

    /**
     * Create from database row (replaces mysql_fetch_array).
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['naam'] ?? $row['name'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            phone: isset($row['telefoon']) ? (string) $row['telefoon'] : null,
            address: isset($row['adres']) ? AddressDTO::fromString((string) $row['adres']) : null,
            createdAt: isset($row['created_at'])
                ? new DateTimeImmutable((string) $row['created_at'])
                : null,
        );
    }

    public function getDisplayName(): string
    {
        return htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
    }
}
