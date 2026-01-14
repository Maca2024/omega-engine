<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

/**
 * Contract for discount rules.
 * Replaces eval()-based discount formulas from database.
 */
interface DiscountRuleInterface
{
    /**
     * Check if this rule applies to the given subtotal.
     */
    public function applies(float $subtotal): bool;

    /**
     * Calculate the discount amount.
     */
    public function calculate(float $subtotal): float;

    /**
     * Get human-readable description of the rule.
     */
    public function getDescription(): string;
}
