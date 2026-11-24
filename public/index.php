<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP's built-in server treats public/author/index.html as a directory front
// controller, so /author/pages arrives with SCRIPT_NAME=/author/index.html and
// PATH_INFO=/pages. Put the request back on the Laravel front controller.
$authorScript = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_ends_with($authorScript, '/author/index.html')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    unset($_SERVER['PATH_INFO']);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
