<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$serverlessDefaults = [
    'LOG_CHANNEL' => 'stderr',
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
];

foreach ($serverlessDefaults as $name => $value) {
    if (getenv($name) === false) {
        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$storagePath = '/tmp/storage';

foreach ([
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

putenv('LARAVEL_STORAGE_PATH='.$storagePath);
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($requestPath === '/__health') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'php-runtime-ready',
        'php' => PHP_VERSION,
        'vendor_autoload' => file_exists(__DIR__.'/../vendor/autoload.php'),
        'app_key_configured' => (string) getenv('APP_KEY') !== '',
        'database_configured' => (string) getenv('DB_CONNECTION') !== '',
        'temporary_storage_writable' => is_writable($storagePath),
    ], JSON_PRETTY_PRINT);
    return;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
