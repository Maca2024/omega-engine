<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

/**
 * Immutable DTO voor user data.
 */
final readonly class UserDTO
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    /**
     * @param array{id: int, email: string, firstname: string, lastname?: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            email: $data['email'],
            firstName: $data['firstname'],
            lastName: $data['lastname'] ?? '',
        );
    }
}
