<?php

namespace App\Kobo;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SyncTokenParser
{
    public function decode(?string $rawToken): SyncToken
    {
        if ($rawToken === '' || $rawToken === null) {
            return new SyncToken();
        }

        $infos = (array) json_decode((string) base64_decode($rawToken, true), true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string,string|null> $data */
        $data = (array) ($infos['data'] ?? []);

        $token = new SyncToken();
        $token->version = $infos['version'] ?? $token->version;

        $token->lastModified = self::timeStampStringToDate($data['books_last_modified'] ?? null);
        $token->lastCreated = self::timeStampStringToDate($data['books_last_created'] ?? null);
        $token->archiveLastModified = self::timeStampStringToDate($data['archive_last_modified'] ?? null);
        $token->readingStateLastModified = self::timeStampStringToDate($data['reading_state_last_modified'] ?? null);
        $token->tagLastModified = self::timeStampStringToDate($data['tags_last_modified'] ?? null);
        $token->rawKoboStoreToken = (string) ($data['raw_kobo_store_token'] ?? null);
        $token->rawKoboStoreToken = trim($token->rawKoboStoreToken) === '' ? null : $token->rawKoboStoreToken;

        return $token;
    }

    private static function timeStampStringToDate(string|null $timeStamp): ?\DateTimeInterface
    {
        if ($timeStamp === null || trim($timeStamp) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable('@'.$timeStamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function encode(SyncToken $token): string
    {
        $data = base64_encode(json_encode(['data' => [
            'books_last_modified' => $token->lastModified?->getTimestamp(),
            'books_last_created' => $token->lastCreated?->getTimestamp(),
            'archive_last_modified' => $token->archiveLastModified?->getTimestamp(),
            'reading_state_last_modified' => $token->readingStateLastModified?->getTimestamp(),
            'tags_last_modified' => $token->tagLastModified?->getTimestamp(),
            'raw_kobo_store_token' => $token->rawKoboStoreToken,
        ], 'version' => $token->version], JSON_THROW_ON_ERROR));

        return $data;
    }

    public function decodeFiltersFromGetParameters(Request $request, SyncToken $syncToken): void
    {
        try {
            // Filter=ALL&DownloadUrlFilter=Generic,Android&PrioritizeRecentReads=true
            $resolver = $syncToken->getFilterResolver();
            $keysToKeepLowercase = array_map('strtolower', $resolver->getDefinedOptions());

            $params = $request->query->all();
            $options = array_filter($params, function (string $key) use ($keysToKeepLowercase) {
                return in_array(strtolower($key), $keysToKeepLowercase, true);
            }, ARRAY_FILTER_USE_KEY);
            $syncToken->filters = $resolver->resolve($options);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }
}
