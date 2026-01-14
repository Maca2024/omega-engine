<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Contracts\DiscountRuleInterface;
use App\Domain\Pricing\Rules\VolumeDiscountRule;

/**
 * Safe discount calculator.
 * Replaces the dangerous EVAL-based discount system.
 *
 * LEGACY CODE (DANGEROUS):
 * ```php
 * if (!empty($item['korting_formule_php'])) {
 *     eval('$regel_totaal = '. $item['korting_formule_php']. ';');
 * }
 * ```
 *
 * MODERN: Type-safe discount rules
 */
final readonly class DiscountCalculator
{
    /**
     * @param list<DiscountRuleInterface> $rules
     */
    public function __construct(
        private array $rules = [],
    ) {}

    public static function withDefaultRules(): self
    {
        return new self([
            new VolumeDiscountRule(threshold: 1000.0, percentage: 10.0),
            new VolumeDiscountRule(threshold: 500.0, percentage: 5.0),
            new VolumeDiscountRule(threshold: 100.0, percentage: 2.0),
        ]);
    }

    public function calculateDiscount(float $subtotal): float
    {
        $totalDiscount = 0.0;

        foreach ($this->rules as $rule) {
            if ($rule->applies($subtotal)) {
                $discount = $rule->calculate($subtotal);
                $totalDiscount = max($totalDiscount, $discount);
            }
        }

        return round($totalDiscount, 2);
    }

    public function getApplicableRules(float $subtotal): array
    {
        return array_filter(
            $this->rules,
            static fn (DiscountRuleInterface $rule): bool => $rule->applies($subtotal),
        );
    }
}
