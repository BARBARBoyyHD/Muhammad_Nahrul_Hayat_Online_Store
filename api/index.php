<?php

error_log('[api/index.php] Starting request: ' . ($_SERVER['REQUEST_URI'] ?? '/'));

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($uri !== '/' && !str_contains($uri, '.php') && is_file(__DIR__ . '/../public' . $uri)) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

error_log('[api/index.php] Autoload loaded');

$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';

foreach (['framework/views', 'framework/cache', 'logs'] as $dir) {
    $path = '/tmp/storage/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

try {
    $app = require __DIR__ . '/../bootstrap/app.php';
    error_log('[api/index.php] App created');

    $app->useStoragePath('/tmp/storage');

    $app->handleRequest(Illuminate\Http\Request::capture());
} catch (\Throwable $e) {
    error_log('[api/index.php] ERROR: ' . $e->getMessage());
    error_log('[api/index.php] FILE: ' . $e->getFile() . ':' . $e->getLine());
    error_log('[api/index.php] TRACE: ' . $e->getTraceAsString());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'internal_error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
