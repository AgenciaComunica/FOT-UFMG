<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Resolve app root for multiple hosting layouts:
// - Local dev: project/public (../)
// - Shared host: public_html/app with project in ~/secretaria (../../secretaria)
$candidateRoots = [
    realpath(__DIR__.'/../../secretaria'),
    realpath(__DIR__.'/..'),
];

$appRoot = null;
foreach ($candidateRoots as $candidateRoot) {
    if ($candidateRoot && is_dir($candidateRoot.'/bootstrap') && is_dir($candidateRoot.'/vendor')) {
        $appRoot = $candidateRoot;
        break;
    }
}

if (! $appRoot) {
    http_response_code(500);
    echo 'Application root path not found.';
    exit(1);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appRoot.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once $appRoot.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
