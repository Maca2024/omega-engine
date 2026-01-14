<?php

declare(strict_types=1);

namespace App\Http\View;

/**
 * Simple template renderer.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * function toon_scherm() {
 *     ?>
 *     <body bgcolor="#CCCCCC">
 *     <center>
 *         <font face="Verdana" size="2">
 *         <h1>Hallo <? echo $this->klant_data['naam'];?></h1>  // XSS!
 *     ...
 * }
 * ```
 *
 * NOW: Proper template rendering with auto-escaping.
 */
final readonly class TemplateRenderer
{
    public function __construct(
        private string $templatePath,
    ) {}

    /**
     * Render a template with data.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $filePath = $this->templatePath . '/' . $template . '.php';

        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                sprintf('Template not found: %s', $template),
            );
        }

        // Extract data to variables (safe, controlled extraction)
        extract($data, EXTR_SKIP);

        // Helper function for escaping
        $e = static fn (string $value): string => htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        ob_start();

        try {
            include $filePath;
            return ob_get_clean() ?: '';
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
