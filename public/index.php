<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Switch to 'dev' mode for some specific IPs (for debugging)
// on your docker compose override file, put a new env: DEV_ENV_BY_IP='["188.154.166.59", "185.50.220.76"]'.
$devIps = json_decode($_SERVER['DEV_ENV_BY_IP']??'[]', true, 512, JSON_THROW_ON_ERROR);
$ip = $_SERVER['HTTP_X_FORWARDED_FOR']??'';

if(is_array($devIps) && in_array($ip, $devIps, true)){
    $_SERVER['APP_ENV'] =  'dev';
}


return fn(array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
