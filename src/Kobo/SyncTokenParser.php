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

        $data = null;
        $version = null;
        $b64decoded = base64_decode($rawToken, false);
        if ($b64decoded === false) {
            throw new BadRequestHttpException('Invalid base64 token');
        }

        // New version is {"typ":1,"ver":null,"ptyp":"SyncToken"}{"InternalSyncToken":"..JWT.."}
        if(str_starts_with($b64decoded, '{"typ":1')){
            return $this->decodeInternalSyncToken($b64decoded);
        }

        // Backward compatibility
        $infos = (array)json_decode((string)$b64decoded, true, 512, JSON_THROW_ON_ERROR);
        /** @var array<string,string|null> $data */
        $data = (array) ($infos['data'] ?? []);

        $token = new SyncToken();
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

    private function timeStampISO8601(?string $timeStamp): ?\DateTimeImmutable
    {
        if ($timeStamp === null || trim($timeStamp) === '') {
            return null;
        }
        try {
            return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $timeStamp);
        } catch (\Exception) {
            return null;
        }
    }

    public function encode(SyncToken $token): string
    {
        return base64_encode(json_encode(['data' => [
            'books_last_modified' => $token->lastModified?->getTimestamp(),
            'books_last_created' => $token->lastCreated?->getTimestamp(),
            'archive_last_modified' => $token->archiveLastModified?->getTimestamp(),
            'reading_state_last_modified' => $token->readingStateLastModified?->getTimestamp(),
            'tags_last_modified' => $token->tagLastModified?->getTimestamp(),
            'raw_kobo_store_token' => $token->rawKoboStoreToken,
        ], 'version' => $token->version], JSON_THROW_ON_ERROR));
    }

    public function decodeFiltersFromGetParameters(Request $request, SyncToken $syncToken): void
    {
        try {
            // Filter=ALL&DownloadUrlFilter=Generic,Android&PrioritizeRecentReads=true
            $resolver = $syncToken->getFilterResolver();
            $keysToKeepLowercase = array_map('strtolower', $resolver->getDefinedOptions());

            $params = $request->query->all();
            $options = array_filter($params, fn (string $key) => in_array(strtolower($key), $keysToKeepLowercase, true), ARRAY_FILTER_USE_KEY);
            $syncToken->filters = $resolver->resolve($options);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    private function decodeInternalSyncToken(string $b64decoded): SyncToken
    {
        $pos = strpos($b64decoded, '}{');
        $b64decoded = substr($b64decoded, $pos + 1);
        $secondPartData = json_decode((string) $b64decoded, true, 512, JSON_THROW_ON_ERROR);
        if(!isset($secondPartData['InternalSyncToken'])) {
            throw new BadRequestHttpException('Invalid base64 token: No InternalSyncToken');
        }

        $jwtToken = $secondPartData['InternalSyncToken'];
        $parts = explode('.', (string)$jwtToken);
        if(count($parts) < 2){
            throw new BadRequestHttpException('Invalid JWT token: '. count($parts). " parts");
        }
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        $version= "SyncToken2Parts";
        $data = json_decode((string)$payload, true, 512, JSON_THROW_ON_ERROR);

        $timestampSubscriptionEntitlements = $this->timeStampISO8601($data['SubscriptionEntitlements']["Timestamp"] ?? null);
        $timestampEntitlements = $this->timeStampISO8601($data['Entitlements']["Timestamp"] ?? null);
        $timestampDeletedEntitlements = $this->timeStampISO8601($data['DeletedEntitlements']["Timestamp"] ?? null);
        $timestampReadingStates = $this->timeStampISO8601($data['ReadingStates']["Timestamp"] ?? null);
        $timestampTags = $this->timeStampISO8601($data['Tags']["Timestamp"] ?? null);
        $timestampDeletedTags = $this->timeStampISO8601($data['DeletedTags']["Timestamp"] ?? null);
        $timestampProductMetadata = $this->timeStampISO8601($data['ProductMetadata']["Timestamp"] ?? null);

        $token = new SyncToken();
        $token->version = "InternalSyncToken";
        $token->lastModified = $timestampEntitlements;
        $token->lastCreated = null;
        $token->archiveLastModified = null;
        $token->readingStateLastModified = $timestampReadingStates;
        $token->tagLastModified = null;

        return $token;
    }
}
