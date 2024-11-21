<?php

namespace App\Controller\Kobo;

use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Repository\KoboDeviceRepository;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboAnalyticsController extends AbstractController
{
    public function __construct(
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected KoboStoreProxy $koboStoreProxy,
        protected KoboDeviceRepository $koboDeviceRepository,
        protected LoggerInterface $logger,
    ) {
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
    public function analyticsEvent(Request $request, KoboDevice $kobo): Response
    {
        // Save the device_id and model
        if ($request->headers->has(KoboDevice::KOBO_DEVICE_ID_HEADER)) {
            $kobo->setDeviceId($request->headers->get(KoboDevice::KOBO_DEVICE_ID_HEADER));
            if ($request->headers->has(KoboDevice::KOBO_DEVICE_MODEL)) {
                $kobo->setModel($request->headers->get(KoboDevice::KOBO_DEVICE_MODEL));
            }
            $this->koboDeviceRepository->save($kobo);
        }

        $content = $request->getContent();
        $json = trim($content) === '' ? [] : (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->logger->debug('Analytics event received', ['body' => $json]);

        $response = new StreamedResponse(function () use ($request) {
            echo json_encode([
                'Result' => 'Success',
                'AcceptedEvents' => [
                    '1eba5308-878a-4997-a7c4-80644a79f6da',
                    '75a68185-ac29-4255-b7e2-c9be02cf85f5',
                ],
                'RejectedEvents' => new \stdClass(),
            ]);

            if ($this->koboStoreProxy->isEnabled()) {
                $proxyResponse = $this->koboStoreProxy->proxyOrRedirect($request);
                if ($proxyResponse->getStatusCode() === Response::HTTP_OK) {
                    $this->logger->debug('Analytics event received with bad status {code}', ['code' => $proxyResponse->getStatusCode()]);
                }
            }
        });

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
