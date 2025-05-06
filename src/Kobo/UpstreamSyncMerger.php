<?php

namespace App\Kobo;

use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponse;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpstreamSyncMerger
{
    public function __construct(
        private readonly KoboStoreProxy $koboStoreProxy,
        private readonly LoggerInterface $koboSyncLogger,
        private readonly SyncTokenParser $syncTokenParser,
        #[Autowire('%kernel.debug')]
        protected bool $kernelDebug,
    ) {
    }

    /**
     * @return array{bool, SyncToken|null} Should continue + Upstream SyncToken
     */
    public function merge(KoboDevice $device, SyncResponse $syncResponse, Request $request, ?Response $httpResponse = null): array
    {
        // Make sure the merge is enabled
        if (false === $device->isUpstreamSync() || false === $this->koboStoreProxy->isEnabled()) {
            $this->koboSyncLogger->debug('Your device {device} has "upstream sync" disabled', [
                'device' => $device->getId(),
            ]);

            return [false, null];
        }

        try {
            $response = $this->koboStoreProxy->proxy($request, ['stream' => false]);
        } catch (GuzzleException $e) {
            $this->koboSyncLogger->error('Unable to sync with upstream: {exception}', [
                'exception' => $e,
            ]);

            return [false, null];
        }
        if (false === $response->isOk()) {
            $this->koboSyncLogger->error('Sync response is not ok. Got '.$response->getStatusCode());

            return [false, null];
        }

        $this->addDebuggingHeaders($httpResponse, $response);

        $json = $this->parseJson($response);

        if ($json === []) {
            return [false, null];
        }

        $this->koboSyncLogger->info('Merging {count} upstream items', [
            'device' => $device->getId(),
            'count' => count($json),
        ]);
        $syncResponse->addRemoteItems($json);

        try {
            $upstreamSyncToken = $this->syncTokenParser->decode($response->headers->get(KoboDevice::KOBO_SYNC_TOKEN_HEADER));
        } catch (\JsonException $e) {
            $this->koboSyncLogger->warning('Unable to decode sync token: {exception}', ['exception' => $e]);
            $upstreamSyncToken = null;
        }

        return [$this->shouldContinue($response), $upstreamSyncToken];
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
        return $response->headers->get(KoboDevice::KOBO_SYNC_SHOULD_CONTINUE_HEADER) === 'continue';
    }

    private function addDebuggingHeaders(?Response $httpResponse, Response $response): void
    {
        if (!$this->kernelDebug || !$httpResponse instanceof Response) {
            return;
        }

        foreach ($response->headers->getIterator() as $name => $values) {
            foreach (((array) $values) as $key => $value) {
                if (is_string($value)) {
                    $httpResponse->headers->set('X-upstream-'.$key.'-'.$name, $value);
                }
            }
        }

        $this->koboSyncLogger->info('Upstream sync headers: {response}', [
            'response' => $response->headers->all(),
        ]);
    }
}
