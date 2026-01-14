<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Validated request voor order verwijdering.
 */
final readonly class DeleteOrderRequest
{
    public function __construct(
        public int $orderId,
    ) {}

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
