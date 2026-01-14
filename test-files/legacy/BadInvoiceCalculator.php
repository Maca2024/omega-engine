<?php

declare(strict_types=1);

namespace Legacy\OldStuff;

class BadInvoiceCalculator {

    /**
     * @param array<int, array{price: float, qty: int}> $items
     * @return array{ex_vat: float, inc_vat: float, rate_used: float}
     */
    public function calculate(array $items, string $tax_rate, string $user_id): array {
        $total = 0.0;
        // Hallucinatie-gevoelig: Oude switch statements
        $rate = match ($tax_rate) {
            'high' => 0.21,
            'low' => 0.09,
            default => 0.0,
        };

        foreach($items as $item) {
            // Gevaarlijke float berekeningen
            $sub = $item['price'] * $item['qty'];
            $total += $sub;
        }

        // Directe side-effect (mag niet in domain logic)
        echo "Calculating for user: ". $user_id. "\n";

        $grand_total = $total * (1 + $rate);

        // Returnt een array in plaats van een object (DTO)
        return [
            "ex_vat" => $total,
            "inc_vat" => $grand_total,
            "rate_used" => $rate
        ];
    }
}