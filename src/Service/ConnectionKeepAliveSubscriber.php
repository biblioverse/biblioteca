<?php

namespace App\Service;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ConnectionKeepAliveSubscriber
{
    #[AsEventListener(event: 'kernel.response', priority: 42)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $version = $event->getRequest()->getProtocolVersion();

        // Keep alive is only supported in HTTP/1.0 and HTTP/1.1
        if (false === in_array($version, ['HTTP/1.0', 'HTTP/1.1'], true)) {
            return;
        }

        // If the controller already set the Connection header, we don't want to override it
        if ($event->getResponse()->headers->has('Connection')) {
            return;
        }

        // We add the Connection: keep-alive header if the client requested it
        $connection = $event->getRequest()->headers->get('Connection');
        if ($connection === 'keep-alive') {
            $event->getResponse()->headers->set('Connection', 'keep-alive');
        }
    }
}
