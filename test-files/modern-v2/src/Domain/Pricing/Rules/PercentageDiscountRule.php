<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Rules;

use App\Domain\Pricing\Contracts\DiscountRuleInterface;

/**
 * Simple percentage discount rule.
 * Can be used for coupon codes, promotions, etc.
 */
final readonly class PercentageDiscountRule implements DiscountRuleInterface
{
    public function __construct(
        private float $percentage,
        private ?float $minimumOrderValue = null,
        private ?float $maximumDiscount = null,
    ) {}

    public function applies(float $subtotal): bool
    {
        if ($this->minimumOrderValue === null) {
            return true;
        }

        return $subtotal >= $this->minimumOrderValue;
    }

    public function calculate(float $subtotal): float
    {
        if (!$this->applies($subtotal)) {
            return 0.0;
        }

        $discount = $subtotal * ($this->percentage / 100);

        if ($this->maximumDiscount !== null) {
            $discount = min($discount, $this->maximumDiscount);
        }

        return round($discount, 2);
    }

    public function getDescription(): string
    {
        $desc = sprintf('%.0f%% discount', $this->percentage);

        if ($this->minimumOrderValue !== null) {
            $desc .= sprintf(' (min. order: %.2f EUR)', $this->minimumOrderValue);
        }

        if ($this->maximumDiscount !== null) {
            $desc .= sprintf(' (max. discount: %.2f EUR)', $this->maximumDiscount);
        }

        return $desc;
    }
}
