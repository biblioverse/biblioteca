<?php

namespace App\Service;

use App\Entity\KoboDevice;
use App\Kobo\SyncToken;
use App\Kobo\SyncTokenParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KoboSyncTokenExtractor
{
    public function __construct(protected SyncTokenParser $syncTokenParser)
    {
    }

    public function get(Request $request): SyncToken
    {
        $syncToken = $this->syncTokenParser->decode($this->extract($request));
        $this->syncTokenParser->decodeFiltersFromGetParameters($request, $syncToken);

        return $syncToken;
    }

    public function set(Response $response, SyncToken $token): Response
    {
        $token = $this->syncTokenParser->encode($token);
        $response->headers->set(KoboDevice::KOBO_SYNC_TOKEN_HEADER, $token);

        return $response;
    }

    /**
     * @return array{'HTTP_X-Kobo-Synctoken': string}
     * @throws \JsonException
     */
    public function getTestHeader(SyncToken $token): array
    {
        $token = $this->syncTokenParser->encode($token);

        return ['HTTP_'.KoboDevice::KOBO_SYNC_TOKEN_HEADER => $token];
    }

    protected function extract(Request $request): ?string
    {
        return $request->headers->get(KoboDevice::KOBO_SYNC_TOKEN_HEADER);
    }

    public function has(Request $request): bool
    {
        return $request->headers->has(KoboDevice::KOBO_SYNC_TOKEN_HEADER);
    }
}
