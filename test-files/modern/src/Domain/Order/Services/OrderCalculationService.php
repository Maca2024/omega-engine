<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\DTOs\CartItemDTO;
use App\Domain\Order\DTOs\OrderTotalsDTO;
use App\Domain\Order\Enums\VatRate;

/**
 * Service voor het berekenen van order totalen.
 *
 * Pure functions - geen side effects, geen database calls.
 * Volledig testbaar en deterministisch.
 */
final readonly class OrderCalculationService
{
    /**
     * Discount thresholds - makkelijk configureerbaar.
     */
    private const float DISCOUNT_THRESHOLD_HIGH = 500.0;
    private const float DISCOUNT_THRESHOLD_LOW = 100.0;
    private const float DISCOUNT_RATE_HIGH = 0.10;
    private const float DISCOUNT_RATE_LOW = 0.05;

    /**
     * Bereken totalen voor een collectie cart items.
     *
     * @param array<CartItemDTO> $items
     */
    public function calculateTotals(array $items): OrderTotalsDTO
    {
        if ($items === []) {
            return OrderTotalsDTO::zero();
        }

        $subtotal = 0.0;
        $vatHigh = 0.0;
        $vatLow = 0.0;
        $totalDiscount = 0.0;

        foreach ($items as $item) {
            $itemSubtotal = $item->subtotal();
            $discount = $this->calculateDiscount($itemSubtotal);
            $discountedSubtotal = $itemSubtotal - $discount;

            $subtotal += $discountedSubtotal;
            $totalDiscount += $discount;

            // Bereken BTW per tarief
            $vatAmount = $discountedSubtotal * $item->vatRate->rate();

            match ($item->vatRate) {
                VatRate::HIGH => $vatHigh += $vatAmount,
                VatRate::LOW => $vatLow += $vatAmount,
                VatRate::ZERO => null, // Geen BTW
            };
        }

        return new OrderTotalsDTO(
            subtotal: $subtotal,
            vatHigh: $vatHigh,
            vatLow: $vatLow,
            discountAmount: $totalDiscount,
            grandTotal: $subtotal + $vatHigh + $vatLow,
        );
    }

    /**
     * Bereken korting op basis van bedrag.
     *
     * Gestaffelde korting:
     * - > 500 euro: 10% korting
     * - > 100 euro: 5% korting
     * - Anders: geen korting
     */
    private function calculateDiscount(float $amount): float
    {
        $rate = match (true) {
            $amount > self::DISCOUNT_THRESHOLD_HIGH => self::DISCOUNT_RATE_HIGH,
            $amount > self::DISCOUNT_THRESHOLD_LOW => self::DISCOUNT_RATE_LOW,
            default => 0.0,
        };

        return $amount * $rate;
    }
}
