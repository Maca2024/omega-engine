<?php

declare(strict_types=1);

namespace App\Http\Responses;

/**
 * JSON Response wrapper.
 */
final readonly class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        public bool $success,
        public array $data,
        public int $statusCode,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data, int $statusCode = 200): self
    {
        return new self(
            success: true,
            data: $data,
            statusCode: $statusCode,
        );
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return new self(
            success: false,
            data: ['error' => $message],
            statusCode: $statusCode,
        );
    }

    public function send(): never
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $this->success,
            ...$this->data,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        exit;
    }
}
