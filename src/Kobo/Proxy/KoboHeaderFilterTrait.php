<?php

namespace App\Kobo\Proxy;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait KoboHeaderFilterTrait
{
    public function getHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            if (str_starts_with(strtolower($key), 'x-kobo-')) {
                $result[$key] = $value;
            }
            if (in_array(strtolower($key), ['authorization', 'host'], true)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function cleanup(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request
            ->withoutHeader('X-Debug-Token')
            ->withoutHeader('X-Debug-Link')
            // ->withoutHeader('X-Powered-By')
            // ->withoutHeader('X-Forwarded-For')
            // ->withoutHeader('X-Forwarded-Port')
            // ->withoutHeader('X-Forwarded-Proto')
            // ->withoutHeader('X-Forwarded-Host')
            // ->withoutHeader('X-Scheme')
            // ->withoutHeader('x-php-ob-level');
        ;
    }

    private function cleanupGuzzle(Response $response): MessageInterface
    {
        return $response
            ->withoutHeader('X-debug-token')
            ->withoutHeader('X-debug-link');
    }

    private function cleanupPsrResponse(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withoutHeader('X-Debug-Token')
            ->withoutHeader('X-Debug-Link')
            ->withoutHeader('X-powered-By')
            ->withoutHeader('X-Forwarded-For')
            ->withoutHeader('X-Forwarded-Port')
            ->withoutHeader('X-Forwarded-Proto')
            ->withoutHeader('X-Forwarded-Host')
            ->withoutHeader('X-Powered-By')
            ->withoutHeader('X-Scheme')
            ->withoutHeader('Server')
            ->withoutHeader('Connection')
            ->withoutHeader('Content-Encoding')
            ->withoutHeader('Content-Length')
            ->withoutHeader('Transfer-Encoding')
            ->withoutHeader('Host')
            ->withoutHeader('Server')
        ;
    }
}
