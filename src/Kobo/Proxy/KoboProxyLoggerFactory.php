<?php

namespace App\Kobo\Proxy;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;

class KoboProxyLoggerFactory
{
    public function __construct(protected KoboProxyConfiguration $configuration, protected LoggerInterface $proxyLogger)
    {
    }

    public function create(string $accessToken): KoboProxyLogger
    {
        return new KoboProxyLogger($this->configuration, $this->proxyLogger, $accessToken);
    }

    public function createStack(string $accessToken): HandlerStack
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $logger = $this->create($accessToken);
        $stack->push($logger);

        return $stack;
    }
}
