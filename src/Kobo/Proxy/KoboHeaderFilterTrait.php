<?php

namespace App\Kobo\Proxy;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

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

    private function cleanupGuzzle(\GuzzleHttp\Psr7\Response $response): MessageInterface
    {
        return $response
            ->withoutHeader('X-debug-token')
            ->withoutHeader('X-debug-link');
    }

    private function cleanupResponse(Response $response): void
    {
        $response->headers->remove('X-debug-token');
        $response->headers->remove('X-debug-link');
        // $response->headers->remove('x-powered-By');
        // $response->headers->remove('x-forwarded-for');
        // $response->headers->remove('x-forwarded-port');
        // $response->headers->remove('x-forwarded-proto');
        // $response->headers->remove('x-forwarded-host');
        // $response->headers->remove('x-scheme');
        // $response->headers->remove('x-php-ob-level');;
        // $response->headers->remove('server');;
        // $response->headers->remove('connection');;
        // $response->headers->remove('content-encoding');;
        // $response->headers->remove('content-length');;
        // $response->headers->remove('"transfer-encoding');;
        // $response->headers->remove('"transfer-encoding');;
    }

    private function cleanupPsrResponse(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withoutHeader('X-Debug-Token')
            ->withoutHeader('X-Debug-Link');
    }
}
