<?php

declare(strict_types=1);

namespace App\Http\Responses;

/**
 * View Response - Template rendering.
 */
final class ViewResponse
{
    private string $content = '';

    public function __construct(
        private readonly string $templatePath,
    ) {}

    /**
     * Render een view template.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): self
    {
        $templateFile = $this->templatePath . '/' . $template . '.php';

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $this->content = (string) ob_get_clean();

        return $this;
    }

    public function error(string $message, int $code): self
    {
        http_response_code($code);
        return $this->render('error', ['message' => $message, 'code' => $code]);
    }

    public function send(): void
    {
        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
