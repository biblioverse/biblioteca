<?php

namespace App\Kobo;

use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponse;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpstreamSyncMerger
{
    public function __construct(
        private readonly KoboStoreProxy $koboStoreProxy,
        private readonly LoggerInterface $koboSyncLogger,
    ) {
    }

    public function merge(KoboDevice $device, SyncResponse $syncResponse, Request $request): bool
    {
        // Make sure the merge is enabled
        if (false === $device->isUpstreamSync() || false === $this->koboStoreProxy->isEnabled()) {
            $this->koboSyncLogger->debug('Your device {device} has "upstream sync" disabled', [
                'device' => $device->getId(),
            ]);

            return false;
        }

        try {
            $response = $this->koboStoreProxy->proxy($request, ['stream' => false]);
        } catch (GuzzleException $e) {
            $this->koboSyncLogger->error('Unable to sync with upstream: {exception}', [
                'exception' => $e,
            ]);

            return false;
        }
        if (false === $response->isOk()) {
            $this->koboSyncLogger->error('Sync response is not ok. Got '.$response->getStatusCode());

            return false;
        }

        $json = $this->parseJson($response);

        if ($json === []) {
            return false;
        }

        $this->koboSyncLogger->info('Merging {count} upstream items', [
            'device' => $device->getId(),
            'count' => count($json),
        ]);
        $syncResponse->addRemoteItems($json);

        return $this->shouldContinue($response);
    }

    private function parseJson(Response $response): array
    {
        try {
            $result = $response->getContent();
            if (false === $result) {
                throw new \RuntimeException('Response content is false. Code: '.$response->getStatusCode());
            }

            return (array) json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->koboSyncLogger->warning('Unable to upstream sync response: {content}', [
                'exception' => $e,
                'content' => $response->getContent(),
            ]);

            return [];
        }
    }

    private function shouldContinue(Response $response): bool
    {
        return $response->headers->get('x-kobo-sync') === 'continue';
    }
}
