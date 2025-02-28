<?php

namespace App\Service;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AccessControlSubscriber
{
    private const string ACCESS_CONTROL_ALLOW_HEADERS = 'Content-Type,Accept,Accept-Language,Accept-Encoding,If-Match,If-None-Match,Content-Language,authorization,x-kobo-appversion,x-kobo-deviceid,x-kobo-deviceos,x-kobo-platformid,x-kobo-use-oauth2,x-kobo-affiliatename,x-kobo-deviceosversion,x-kobo-devicemodel,x-kobo-carriername,x-kobo-web,traceparent';
    private const string ACCESS_CONTROL_ALLOW_METHODS = 'GET, PUT, POST, DELETE, PATCH, HEAD, OPTIONS';
    private const string HEADER_ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';
    private const string HEADER_ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';

    #[AsEventListener(event: 'kernel.response', priority: 42)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (false === $event->getRequest()->attributes->has('isKoboRequest')) {
            return;
        }

        $this->addHeaderIfNotSet($event->getResponse(), self::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, self::ACCESS_CONTROL_ALLOW_HEADERS);
        $this->addHeaderIfNotSet($event->getResponse(), self::HEADER_ACCESS_CONTROL_ALLOW_METHODS, self::ACCESS_CONTROL_ALLOW_METHODS);
    }

    private function addHeaderIfNotSet(Response $response, string $name, string $value): void
    {
        if ($response->headers->has($name)) {
            return;
        }

        $response->headers->set($name, $value);
    }
}
