<?php
declare(strict_types=1);

/**
 * Personal Finance Manager
 * Web Front Controller
 */

// Define execution start time
define('PFM_START', microtime(true));

// Locate bootstrap file (supports ../finance_app/ or fallback local finance_app/)
$bootstrapPaths = [
    dirname(__DIR__) . '/finance_app/bootstrap.php',
    __DIR__ . '/finance_app/bootstrap.php',
];

$bootstrap = null;
foreach ($bootstrapPaths as $path) {
    if (file_exists($path)) {
        $bootstrap = $path;
        break;
    }
}

if (!$bootstrap) {
    http_response_code(500);
    die("<h1>Configuration Error</h1><p>Unable to locate the application core (finance_app/bootstrap.php).</p>");
}

/** @var \App\Core\Router $router */
$router = require_once $bootstrap;

// Capture incoming HTTP request
$request = \App\Core\Request::capture();

// Dispatch route and send response
$response = $router->dispatch($request);
$response->send();
