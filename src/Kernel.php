<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(
        protected string $environment,
        protected bool $debug,
    ) {
        if ($this->shouldSwitchToDevFromTrustedIPs() && $this->environment === 'prod') {
            $this->environment = 'dev';
            $this->debug = true;
        }
        parent::__construct($this->environment, $this->debug);
    }

    /**
     * Switch to 'dev' mode for some specific IPs (for debugging)
     * on your docker compose override file, put a new env: DEV_ENV_BY_IP="188.154.166.43,185.50.220.65"
     *
     * you can also use a domain name, it will be resolved to an IP.
     **/
    private function shouldSwitchToDevFromTrustedIPs(): bool
    {
        // Get config
        $devIps = explode(',', $this->getEnvAsString('DEV_ENV_BY_IP'));
        if ($devIps[0] === '') {
            return false;
        }

        // Convert domain names to IPs
        $devIps = array_map(function (string $ip): string {
            $value = filter_var(trim($ip), FILTER_VALIDATE_IP);

            return $value === false ? gethostbyname($ip) : $value;
        }, $devIps);

        // Get current Ips
        $forwardedIps = explode(',', $this->getEnvAsString('HTTP_X_FORWARDED_FOR', [$_SERVER]));
        $forwardedIps[] = $this->getEnvAsString('REMOTE_ADDR', [$_SERVER]);

        // Check if the current IP is in the whitelist
        return array_intersect($forwardedIps, $devIps) !== [];
    }

    /**
     * @param list<array<string,mixed>> $from
     */
    private function getEnvAsString(string $name, ?array $from = null): string
    {
        $from ??= [$_ENV, $_SERVER];
        foreach ($from as $source) {
            $value = $source[$name] ?? '';
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}
