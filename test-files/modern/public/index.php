<?php

declare(strict_types=1);

/**
 * Application Entry Point.
 *
 * Dit vervangt de directe code executie in het legacy bestand.
 * Alle dependencies worden hier geïnjecteerd (DI Container).
 *
 * @author AetherLink.AI Tech
 * @version 2.0.0 (PHP 8.4)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Controllers\OrderController;
use App\Domain\Order\Services\OrderService;
use App\Domain\Order\Services\OrderCalculationService;
use App\Infrastructure\Persistence\PdoOrderRepository;
use App\Infrastructure\Mail\QueuedOrderEventDispatcher;
use App\Infrastructure\Mail\OrderMailer;
use App\Http\Validation\RequestValidator;
use App\Http\Responses\ViewResponse;
use App\Http\Responses\JsonResponse;

// ============================================================================
// ERROR HANDLING
// ============================================================================

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $e): void {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'error' => 'Er is een interne fout opgetreden',
    ], JSON_THROW_ON_ERROR);

    exit;
});

// ============================================================================
// DEPENDENCY INJECTION (in productie: gebruik een DI Container zoals PHP-DI)
// ============================================================================

$dsn = $_ENV['DATABASE_DSN'] ?? 'mysql:host=localhost;dbname=shop;charset=utf8mb4';
$dbUser = $_ENV['DATABASE_USER'] ?? 'root';
$dbPass = $_ENV['DATABASE_PASS'] ?? '';

try {
    $pdo = new PDO(
        dsn: $dsn,
        username: $dbUser,
        password: $dbPass,
        options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    JsonResponse::error('Database verbinding mislukt', 500)->send();
}

$repository = new PdoOrderRepository($pdo);
$calculator = new OrderCalculationService();
$mailer = new OrderMailer(
    fromAddress: $_ENV['MAIL_FROM'] ?? 'noreply@aetherlink.ai',
    fromName: 'AetherLink.AI Tech',
);
$eventDispatcher = new QueuedOrderEventDispatcher($mailer);

$orderService = new OrderService($repository, $calculator, $eventDispatcher);
$validator = new RequestValidator();
$view = new ViewResponse(__DIR__ . '/../templates');

$controller = new OrderController($orderService, $validator, $view);

// ============================================================================
// REQUEST HANDLING
// ============================================================================

// Valideer user ID (moet authenticated zijn)
$userId = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);

if ($userId === null || $userId === false || $userId < 1) {
    JsonResponse::error('Authenticatie vereist - uid parameter ontbreekt', 401)->send();
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'form';

// Route naar controller
$response = $controller->handle($action, $userId);

if ($response instanceof JsonResponse) {
    $response->send();
} elseif ($response instanceof ViewResponse) {
    $response->send();
}
