<?php

declare(strict_types=1);

use App\Domain\Order\Exceptions\ValidationException;
use App\Http\Validation\OrderRequestValidator;

describe('OrderRequestValidator', function (): void {

    beforeEach(function (): void {
        $this->validator = new OrderRequestValidator();
    });

    describe('validateCreateOrderRequest', function (): void {

        it('validates valid request', function (): void {
            $data = [
                'customer_id' => 123,
                'mandje' => [
                    1 => 2,
                    2 => 3,
                ],
            ];

            $result = $this->validator->validateCreateOrderRequest($data);

            expect($result['customer_id'])->toBe(123);
            expect($result['cart'])->toHaveKey(1);
            expect($result['cart'])->toHaveKey(2);
        });

        it('throws on missing customer_id', function (): void {
            $data = [
                'mandje' => [1 => 2],
            ];

            expect(fn () => $this->validator->validateCreateOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('throws on invalid customer_id', function (): void {
            $data = [
                'customer_id' => 'invalid',
                'mandje' => [1 => 2],
            ];

            expect(fn () => $this->validator->validateCreateOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('throws on negative customer_id', function (): void {
            $data = [
                'customer_id' => -1,
                'mandje' => [1 => 2],
            ];

            expect(fn () => $this->validator->validateCreateOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('throws on empty cart', function (): void {
            $data = [
                'customer_id' => 123,
                'mandje' => [],
            ];

            expect(fn () => $this->validator->validateCreateOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('filters out zero quantities from cart', function (): void {
            $data = [
                'customer_id' => 123,
                'mandje' => [
                    1 => 2,
                    2 => 0,  // Should be filtered out
                    3 => 1,
                ],
            ];

            $result = $this->validator->validateCreateOrderRequest($data);

            expect($result['cart'])->toHaveKey(1);
            expect($result['cart'])->not->toHaveKey(2);
            expect($result['cart'])->toHaveKey(3);
        });

        it('limits quantity to 999', function (): void {
            $data = [
                'customer_id' => 123,
                'mandje' => [
                    1 => 9999,
                ],
            ];

            $result = $this->validator->validateCreateOrderRequest($data);

            expect($result['cart'][1])->toBe(999);
        });

        it('accepts cart with English key name', function (): void {
            $data = [
                'customer_id' => 123,
                'cart' => [1 => 5],
            ];

            $result = $this->validator->validateCreateOrderRequest($data);

            expect($result['cart'])->toHaveKey(1);
        });

    });

    describe('validateDeleteOrderRequest', function (): void {

        it('validates valid request with order_id', function (): void {
            $data = [
                'order_id' => 456,
                'customer_id' => 123,
            ];

            $result = $this->validator->validateDeleteOrderRequest($data);

            expect($result['order_id'])->toBe(456);
            expect($result['customer_id'])->toBe(123);
        });

        it('accepts legacy oid parameter', function (): void {
            $data = [
                'oid' => 789,
                'customer_id' => 123,
            ];

            $result = $this->validator->validateDeleteOrderRequest($data);

            expect($result['order_id'])->toBe(789);
        });

        it('throws on missing order_id', function (): void {
            $data = [
                'customer_id' => 123,
            ];

            expect(fn () => $this->validator->validateDeleteOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('throws on missing customer_id', function (): void {
            $data = [
                'order_id' => 456,
            ];

            expect(fn () => $this->validator->validateDeleteOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

        it('throws on invalid order_id', function (): void {
            $data = [
                'order_id' => 'invalid',
                'customer_id' => 123,
            ];

            expect(fn () => $this->validator->validateDeleteOrderRequest($data))
                ->toThrow(ValidationException::class);
        });

    });

});
