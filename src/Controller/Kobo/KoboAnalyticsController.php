<?php

namespace App\Controller\Kobo;

use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboAnalyticsController extends AbstractController
{
    public function __construct(protected KoboProxyConfiguration $koboProxyConfiguration, protected KoboStoreProxy $koboStoreProxy, protected LoggerInterface $logger)
    {
    }

    /**
     * Kobo use this endpoint to check the connectivity, if the TCP Connection stays open, etc.
     * @throws GuzzleException
     * @throws \JsonException
     */
    #[Route('/v1/analytics/gettests', methods: ['POST', 'GET'])]
    public function analyticsTests(Request $request): Response
    {
        $content = $request->getContent();
        $json = trim($content) === '' ? [] : (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->logger->info('Analytics tests request', $json);

        $testKey = $json['TestKey'] ?? $request->headers->get('TestKey');

        return new JsonResponse([
            'Result' => 'Success',
            'TestKey' => $testKey,
            'Tests' => new \stdClass(),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws GuzzleException
     */
    #[Route('/v1/analytics/event', methods: ['POST'])]
    public function analyticsEvent(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            $proxyResponse = $this->koboStoreProxy->proxyOrRedirect($request);
            if ($proxyResponse->getStatusCode() === 200) {
                return $proxyResponse;
            }
            $this->logger->debug('Analytics event received with bad status {code}, not proxying it', ['code' => $proxyResponse->getStatusCode()]);
        }

        $content = $request->getContent();
        $json = trim($content) === '' ? [] : (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->logger->debug('Analytics event received', ['body' => $json]);

        return new JsonResponse([
            'Result' => 'Success',
            'AcceptedEvents' => [
                '1eba5308-878a-4997-a7c4-80644a79f6da',
                '75a68185-ac29-4255-b7e2-c9be02cf85f5',
            ],
            'RejectedEvents' => new \stdClass(),
        ]);
    }
}
