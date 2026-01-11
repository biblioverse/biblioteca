<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/kobo/{accessKey}/v1', name: 'kobo_')]
class InitializationController extends AbstractKoboController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected LoggerInterface $koboSyncLogger,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/initialization')]
    public function initialization(Request $request, KoboDevice $koboDevice): Response
    {
        $this->koboSyncLogger->info('Initialization request');
        // Load the JSON data from the store
        // A hardcoded value is returned as fallback (see KoboProxyConfiguration::getNativeInitializationJson)
        $jsonData = $this->getJsonData($request);

        // Make sure the response is wrapped in a Resources key
        if (false === array_key_exists('Resources', $jsonData)) {
            $jsonData = ['Resources' => $jsonData];
        }

        // Override the Image Endpoint with the one from this server
        $base = $this->generateUrl('kobo_api_endpoint', [
            'accessKey' => $koboDevice->getAccessKey(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $base = rtrim($base, '/');

        // Image host: https://<domain>
        $jsonData['Resources']['image_host'] = rtrim($this->generateUrl('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');
        $jsonData['Resources']['image_url_template'] = $base.'/image/{ImageId}/{width}/{height}/{Quality}/isGreyscale/image.jpg';
        $jsonData['Resources']['image_url_quality_template'] = $base.'/{ImageId}/{width}/{height}/false/image.jpg';

        // Original value is "https://readingservices.kobo.com" event if the name is "host", it's an url.
        $jsonData['Resources']['reading_services_host'] = rtrim($this->generateUrl('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        foreach ($jsonData['Resources'] as &$url) {
            if (!is_string($url)) {
                continue;
            }
            $url = str_replace('https://storeapi.kobo.com', $base, $url);
        }

        $response = new JsonResponse($jsonData);
        $response->headers->set(KoboDevice::KOBO_API_TOKEN, KoboDevice::KOBO_API_TOKEN_VALUE);

        return $response;
    }

    /**
     * Fetch initial data from Kobo's API, fallback to local configuration if it fails
     */
    private function getJsonData(Request $request): array
    {
        $genericData = $this->koboProxyConfiguration->getNativeInitializationJson();

        // Proxy is disabled, return the generic data
        if ($this->koboProxyConfiguration->useProxy() === false) {
            $this->koboSyncLogger->info('Proxy is disabled, returning generic data');

            return $genericData;
        }

        try {
            // Load configuration from Kobo
            $jsonResponse = $this->koboStoreProxy->proxyAndFollowRedirects($request, ['stream' => false]);
            if ($jsonResponse->getStatusCode() !== Response::HTTP_OK) {
                throw new \RuntimeException('Bad status code: '.$jsonResponse->getStatusCode());
            }

            return (array) json_decode((string) $jsonResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException|\RuntimeException|\JsonException $exception) {
            $this->koboSyncLogger->warning('Unable to fetch initialization data', ['exception' => $exception]);

            return $genericData;
        }
    }
}
