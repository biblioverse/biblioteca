<?php

namespace App\Kobo\SyncToken;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Encode or decode a token to a HTTP Header value
 */
class SyncTokenParser
{
    /**
     * @throws \JsonException
     */
    public function decode(?string $rawToken): SyncTokenInterface
    {
        if ($rawToken === '' || $rawToken === null) {
            return new SyncTokenV1();
        }

        // If there is a . we have token v2.
        if (str_contains($rawToken, '.')) {
            return $this->decodeV2Token($rawToken);
        }

        // Backward compatibility
        return $this->decodeV1Token($rawToken);
    }

    private function timeStampStringToDate(?string $timeStamp): ?\DateTimeImmutable
    {
        if ($timeStamp === null || trim($timeStamp) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable('@'.$timeStamp);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @throws \JsonException
     */
    public function encode(SyncTokenInterface $token): string
    {
        if ($token instanceof SyncTokenV2) {
            return base64_encode(SyncTokenV2::HEADER_NO_VERSION).'.'.base64_encode(json_encode([
                'InternalSyncToken' => base64_encode(SyncTokenV2::HEADER).'.'.base64_encode(json_encode($token->toArray()['data'], JSON_THROW_ON_ERROR)),
                'IsContinuationToken' => $token->isContinuation(),
            ], JSON_THROW_ON_ERROR));
        }

        try {
            assert($token instanceof SyncTokenV1);

            $data = [
                'books_last_modified' => $token->getLastModified()?->getTimestamp(),
                'books_last_created' => $token->getLastCreated()?->getTimestamp(),
                'archive_last_modified' => $token->getArchiveLastModified()?->getTimestamp(),
                'reading_state_last_modified' => $token->getReadingStateLastModified()?->getTimestamp(),
                'tags_last_modified' => $token->getTagLastModified()?->getTimestamp(),
                'raw_kobo_store_token' => $token->rawKoboStoreToken,
            ];

            return base64_encode(json_encode(['data' => $data, 'version' => $token->version], JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function decodeFiltersFromGetParameters(Request $request, SyncTokenInterface $syncToken): void
    {
        try {
            // Filter=ALL&DownloadUrlFilter=Generic,Android&PrioritizeRecentReads=true
            $resolver = $this->getFilterResolver();
            $keysToKeepLowercase = array_map('strtolower', $resolver->getDefinedOptions());

            $params = $request->query->all();
            $options = array_filter($params, fn (string $key) => in_array(strtolower($key), $keysToKeepLowercase, true), ARRAY_FILTER_USE_KEY);
            $syncToken->setFilters($resolver->resolve($options));
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    /**
     * Decode the token
     * It's 2 base64 payload separated by a dot.
     * - first payload is: {"typ":1,"ver":null,"ptyp":"SyncToken"}
     * - Second payload is: {"InternalSyncToken":"..JWT.."}
     * @throws \JsonException
     */
    private function decodeV2Token(string $rawToken): SyncTokenV2
    {
        $parts = explode('.', $rawToken);
        if (count($parts) < 2) {
            throw new \RuntimeException('Invalid token, should have 2 parts');
        }
        $header = base64_decode($parts[0], true);
        $content = base64_decode($parts[1], true);
        if ($header === false || $content === false) {
            throw new \RuntimeException("Invalid token. Can't decode one of the parts");
        }
        $b64decoded = $header.''.$content;

        $pos = strpos($b64decoded, '}{');
        if ($pos === false) {
            throw new BadRequestHttpException('Syntax error with internal sync token }{ not found.');
        }
        $b64decoded = substr($b64decoded, $pos + 1);
        /** @var array{InternalSyncToken?: string, 'IsContinuationToken'?: bool} $secondPartData */
        $secondPartData = json_decode($b64decoded, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($secondPartData['InternalSyncToken'])) {
            throw new BadRequestHttpException('Invalid base64 token: No InternalSyncToken');
        }

        $jwtToken = $secondPartData['InternalSyncToken'];
        $parts = explode('.', $jwtToken);
        if (count($parts) < 2) {
            throw new BadRequestHttpException('Invalid JWT token: '.count($parts).' parts');
        }
        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payload === false) {
            throw new BadRequestHttpException('Invalid base64 token: InternalSyncToken decode failed');
        }
        /** @var array<string, array{'CheckSum'?:string|int|null, 'GenerationTime'?: string, 'Timestamp'?:string, 'IsInitial'?: bool|null, 'Id'?:string}> $data */
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return new SyncTokenV2($data, $secondPartData['IsContinuationToken'] ?? null);
    }

    private function getFilterResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'Filter' => 'ALL',
            'DownloadUrlFilter' => 'Generic,Android',
            'PrioritizeRecentReads' => true,
        ]);
        $resolver->setAllowedTypes('Filter', 'string');
        $resolver->setAllowedTypes('DownloadUrlFilter', ['string', 'array', 'null']);
        $resolver->setAllowedValues('DownloadUrlFilter', ['Generic', 'Android', 'Generic,Android']);
        $resolver->setAllowedTypes('PrioritizeRecentReads', ['string', 'bool']);

        $resolver->setNormalizer('DownloadUrlFilter', function (Options $options, string|array|null $value) {
            $result = is_array($value) ? $value : explode(',', (string) $value);
            if ($result === []) {
                return null;
            }
        });
        $resolver->setNormalizer('PrioritizeRecentReads', fn (Options $options, string|bool $value) => in_array(strtolower((string) $value), ['true', '1', 'yes'], true));

        return $resolver;
    }

    private function decodeV1Token(string $rawToken): SyncTokenV1
    {
        $b64decoded = base64_decode($rawToken, true);
        if ($b64decoded === false) {
            throw new \RuntimeException("Invalid token. Can't decode v1");
        }
        $infos = (array) json_decode($b64decoded, true, 512, JSON_THROW_ON_ERROR);
        /** @var array<string,string|null> $data */
        $data = (array) ($infos['data'] ?? []);

        $token = new SyncTokenV1();
        $token->version = $infos['version'] ?? $token->version;

        $token->lastModified = $this->timeStampStringToDate($data['books_last_modified'] ?? null);
        $token->lastCreated = $this->timeStampStringToDate($data['books_last_created'] ?? null);
        $token->archiveLastModified = $this->timeStampStringToDate($data['archive_last_modified'] ?? null);
        $token->readingStateLastModified = $this->timeStampStringToDate($data['reading_state_last_modified'] ?? null);
        $token->tagLastModified = $this->timeStampStringToDate($data['tags_last_modified'] ?? null);
        $token->rawKoboStoreToken = (string) ($data['raw_kobo_store_token'] ?? null);
        $token->rawKoboStoreToken = trim($token->rawKoboStoreToken) === '' ? null : $token->rawKoboStoreToken;

        return $token;
    }
}
