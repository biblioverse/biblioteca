<?php

namespace App\Security;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class OpdsTokenExtractor implements AccessTokenExtractorInterface
{
    /**
     * Kobos are sending the access token in the URL (/kobo/{token})
     */
    public function extractAccessToken(Request $request): ?string
    {
        $uri = $request->getRequestUri();

        return $this->extractAccessTokenFromUri($uri);
    }

    public function extractAccessTokenFromServerRequest(ServerRequestInterface $request): ?string
    {
        $uri = $request->getUri()->getPath();

        return $this->extractAccessTokenFromUri($uri);
    }

    public function getOriginalPath(ServerRequestInterface $request, string $path): string
    {
        $token = $this->extractAccessTokenFromServerRequest($request);
        if ($token === null) {
            return $path;
        }

        return str_replace('/opds/'.$token, '', $path);
    }

    private function extractAccessTokenFromUri(string $uri): ?string
    {
        $uri = explode('/', $uri);
        if (count($uri) >= 3 && $uri[1] === 'opds') {
            return $uri[2];
        }

        return null;
    }
}
