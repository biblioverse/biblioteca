<?php

namespace App\Controller\Kobo;

use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/kobo/{accessKey}')]
class KoboInitializationController extends AbstractController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/v1/initialization')]
    public function initialization(Request $request, KoboDevice $kobo): Response
    {
        $this->logger->info('Initialization request');
        // Load the JSON data from the store
        // A hardcoded value is returned as fallback (see KoboProxyConfiguration::getNativeInitializationJson)
        $jsonData = $this->getJsonData($request);

        // Make sure the response is wrapped in a Resources key
        if (false === key_exists('Resources', $jsonData)) {
            $jsonData = ['Resources' => $jsonData];
        }

        // Override the Image Endpoint with the one from this server
        $base = $this->generateUrl('koboapi_endpoint', [
            'accessKey' => $kobo->getAccessKey(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $base = rtrim($base, '/');

        // Image host: https://<domain>
        $jsonData['Resources']['image_host'] = rtrim($this->generateUrl('app_dashboard', [], UrlGenerator::ABSOLUTE_URL), '/');

        // TODO: Use router instead of hard-coding path.
        $jsonData['Resources']['image_url_template'] = $base.'/image/{ImageId}/{width}/{height}/{Quality}/isGreyscale/image.jpg';
        $jsonData['Resources']['image_url_quality_template'] = $base.'/{ImageId}/{width}/{height}/false/image.jpg';

        $response = new JsonResponse($jsonData);
        $response->headers->set('kobo-api-token', 'e30=');

        return $response;
    }

    /**
     * Fetch initial data from Kobo's API, fallback to local configuration if it fails
     * @param Request $request
     * @return array
     */
    private function getJsonData(Request $request): array
    {
        $genericData = $this->koboProxyConfiguration->getNativeInitializationJson();

        // Proxy is disabled, return the generic data
        if ($this->koboProxyConfiguration->useProxy() === false) {
            $this->logger->info('Proxy is disabled, returning generic data');

            return $genericData;
        }

        try {
            // Load configuration from Kobo
            $jsonResponse = $this->koboStoreProxy->proxyAndFollowRedirects($request, ['stream' => false]);
            if ($jsonResponse->getStatusCode() === 401) {
                throw new \RuntimeException('Unauthorized request while contacting Kobo API');
            }
            if ($jsonResponse->getStatusCode() !== 200) {
                throw new \RuntimeException('Bad status code');
            }

            return (array) json_decode((string) $jsonResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException|\RuntimeException|\JsonException $exception) {
            $this->logger->warning('Unable to fetch initialization data', ['exception' => $exception]);

            return $genericData;
        }
    }
}
