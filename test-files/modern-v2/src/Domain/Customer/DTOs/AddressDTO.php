<?php

declare(strict_types=1);

namespace App\Domain\Customer\DTOs;

final readonly class AddressDTO
{
    public function __construct(
        public string $street,
        public string $houseNumber,
        public string $postalCode,
        public string $city,
        public string $country = 'NL',
    ) {}

    public static function fromString(string $address): self
    {
        // Parse legacy address format: "Street 123, 1234AB City"
        $parts = explode(',', $address);
        $streetPart = trim($parts[0] ?? '');
        $cityPart = trim($parts[1] ?? '');

        // Extract house number from street
        preg_match('/^(.+?)\s+(\d+\S*)$/', $streetPart, $streetMatches);
        $street = $streetMatches[1] ?? $streetPart;
        $houseNumber = $streetMatches[2] ?? '';

        // Extract postal code from city part
        preg_match('/^(\d{4}\s*[A-Z]{2})\s*(.*)$/', $cityPart, $cityMatches);
        $postalCode = $cityMatches[1] ?? '';
        $city = $cityMatches[2] ?? $cityPart;

        return new self(
            street: $street,
            houseNumber: $houseNumber,
            postalCode: $postalCode,
            city: $city,
        );
    }

    public function format(): string
    {
        return sprintf(
            '%s %s, %s %s',
            $this->street,
            $this->houseNumber,
            $this->postalCode,
            $this->city,
        );
    }
}
