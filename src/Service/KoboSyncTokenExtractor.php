<?php

namespace App\Service;

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
        $response->headers->set('kobo-synctoken', $token);

        return $response;
    }

    protected function extract(Request $request): ?string
    {
        return $request->headers->get('kobo-synctoken');
    }
}
