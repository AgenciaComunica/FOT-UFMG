<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Allow running with public/ inside a subdomain and app root in /ComunicaSaaS.
$appRoot = realpath(__DIR__.'/../../ComunicaSaaS') ?: realpath(__DIR__.'/..');

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
