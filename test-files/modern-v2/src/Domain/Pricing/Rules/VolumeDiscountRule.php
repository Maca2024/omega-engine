<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Rules;

use App\Domain\Pricing\Contracts\DiscountRuleInterface;

/**
 * Volume-based discount rule.
 * Example: 10% off orders over 1000 EUR.
 */
final readonly class VolumeDiscountRule implements DiscountRuleInterface
{
    public function __construct(
        private float $threshold,
        private float $percentage,
    ) {}

    public function applies(float $subtotal): bool
    {
        return $subtotal >= $this->threshold;
    }

    public function calculate(float $subtotal): float
    {
        if (!$this->applies($subtotal)) {
            return 0.0;
        }

        return round($subtotal * ($this->percentage / 100), 2);
    }

    public function getDescription(): string
    {
        return sprintf(
            '%.0f%% discount on orders over %.2f EUR',
            $this->percentage,
            $this->threshold,
        );
    }
}
