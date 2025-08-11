<?php
// FrankenPHP Worker Script for Symfony
// This allows the application to stay in memory between requests

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

require dirname(__DIR__).'/vendor/autoload.php';

// Load environment variables
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Enable debug mode if needed
if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

// Create the kernel once
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Handle requests in a loop
while ($request = Request::createFromGlobals()) {
    try {
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    } catch (\Throwable $e) {
        // Log the error and continue with the next request
        error_log('Worker error: ' . $e->getMessage());
        // Optionally restart the kernel
        $kernel->reboot(null);
    }
}