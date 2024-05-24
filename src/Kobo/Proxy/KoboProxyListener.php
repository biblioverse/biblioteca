<?php

namespace App\Kobo\Proxy;

use App\Security\KoboTokenExtractor;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KoboProxyListener
{
    public const CONNECTION_SPECIFIC_HEADERS = [
        'connection',
        'content-encoding',
        'content-length',
        'transfer-encoding',
    ];

    public function __construct(
        private readonly KoboStoreProxy $proxy,
        private readonly KoboProxyConfiguration $configuration,
        private readonly KoboTokenExtractor $tokenExtractor,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // If the proxy is disabled, we don't need to do anything
        // Except if the request is blacklisted (Ex: initialisation)
        if ($this->configuration->useProxyEverywhere() === false) {
            return;
        }

        // If the request is not a Kobo request, we don't need to do anything
        $request = $event->getRequest();
        if (false === $this->tokenExtractor->isKoboUrl($request)) {
            return;
        }

        // Proxy the request
        $response = $this->proxy->proxy($request, ['stream' => true]);

        // Remove the connection specific header (retrieved from proxy), so Symfony can handle it..
        foreach (self::CONNECTION_SPECIFIC_HEADERS as $connectionSpecificHeader) {
            $response->headers->remove($connectionSpecificHeader);
        }

        $event->setResponse($response);
        $event->stopPropagation();
    }
}
