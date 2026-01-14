<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Cart\DTOs\CartDTO;
use App\Domain\Cart\DTOs\CartItemDTO;
use App\Domain\Pricing\DTOs\PriceBreakdownDTO;

/**
 * Pure price calculation service.
 * No side effects, no database calls, no external dependencies.
 * Replaces the toxic bereken_prijs() method with EVAL.
 */
final readonly class PriceCalculationService
{
    public function __construct(
        private DiscountCalculator $discountCalculator,
    ) {}

    public function calculatePriceBreakdown(CartDTO $cart): PriceBreakdownDTO
    {
        $subtotal = $this->calculateSubtotal($cart);
        $discountAmount = $this->discountCalculator->calculateDiscount($subtotal);
        $discountedSubtotal = $subtotal - $discountAmount;
        $vatBreakdown = $this->calculateVatBreakdown($cart, $discountAmount, $subtotal);
        $totalVat = array_sum($vatBreakdown);
        $grandTotal = $discountedSubtotal + $totalVat;

        return new PriceBreakdownDTO(
            subtotal: round($subtotal, 2),
            discountAmount: round($discountAmount, 2),
            discountedSubtotal: round($discountedSubtotal, 2),
            vatBreakdown: array_map(
                static fn (float $amount): float => round($amount, 2),
                $vatBreakdown,
            ),
            totalVat: round($totalVat, 2),
            grandTotal: round($grandTotal, 2),
        );
    }

    private function calculateSubtotal(CartDTO $cart): float
    {
        return array_sum(array_map(
            static fn (CartItemDTO $item): float => $item->subtotal(),
            $cart->items,
        ));
    }

    /**
     * Calculate VAT breakdown by category, with discount proportionally applied.
     *
     * @return array<string, float>
     */
    private function calculateVatBreakdown(
        CartDTO $cart,
        float $discountAmount,
        float $subtotal,
    ): array {
        if ($subtotal <= 0) {
            return [];
        }

        $discountRatio = $discountAmount / $subtotal;
        $breakdown = [];

        foreach ($cart->items as $item) {
            $category = $item->vatCategory->value;
            $itemSubtotal = $item->subtotal();
            $itemDiscount = $itemSubtotal * $discountRatio;
            $discountedItemSubtotal = $itemSubtotal - $itemDiscount;
            $itemVat = $discountedItemSubtotal * $item->vatCategory->rate();

            $breakdown[$category] = ($breakdown[$category] ?? 0.0) + $itemVat;
        }

        return $breakdown;
    }
}
