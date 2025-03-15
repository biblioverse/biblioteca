<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Switch to 'dev' mode for some specific IPs (for debugging)
// on your docker compose override file, put a new env: DEV_ENV_BY_IP="188.154.166.43,185.50.220.65"
// you can also use a domain name, it will be resolved to an IP
$devIps = explode(",", (string) $_ENV['DEV_ENV_BY_IP']??$_SERVER['DEV_ENV_BY_IP'] ?? '');
if($devIps[0] !== '') {
    $devIps = array_map(fn($ip) => filter_var(trim($ip), FILTER_VALIDATE_IP) ?: gethostbyname($ip), $devIps);
    $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    $forwardedIps[] = $_SERVER['REMOTE_ADDR'] ?? '';

    if (array_intersect($forwardedIps, $devIps) !== []) {
        $_SERVER['APP_ENV'] = $_SERVER['APP_ENV'] = 'dev';
    }
}


return fn(array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
