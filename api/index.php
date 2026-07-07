<?php

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($uri !== '/' && !str_contains($uri, '.php') && is_file(__DIR__ . '/../public' . $uri)) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';

foreach (['framework/views', 'framework/cache', 'logs'] as $dir) {
    $path = '/tmp/storage/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

$app = require __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath('/tmp/storage');

$app->handleRequest(Illuminate\Http\Request::capture());
