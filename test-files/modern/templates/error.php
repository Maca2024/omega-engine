<?php
/**
 * Error Page Template.
 *
 * @var string $message
 * @var int $code
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= (int) $code ?> - AetherLink.AI Tech</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f3f4f6;
            color: #1f2937;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #ef4444;
            margin-bottom: 16px;
        }
        .error-message {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 24px;
        }
        a {
            color: #2563eb;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= (int) $code ?></div>
        <div class="error-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <a href="/">Terug naar home</a>
    </div>
</body>
</html>
