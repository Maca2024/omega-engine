<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Validated request voor order creatie.
 */
final readonly class CreateOrderRequest
{
    /**
     * @param array<array{id: int, price: float, qty: int, type: int}> $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * Validatie regels.
     *
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.type' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
