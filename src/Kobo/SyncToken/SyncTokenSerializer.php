<?php

namespace App\Kobo\SyncToken;

/**
 * Encode or decode a token for storing it into DB.
 */
class SyncTokenSerializer
{
    public static function toArray(SyncTokenInterface $syncToken): array
    {
        return $syncToken->toArray();
    }

    public static function fromArray(array $lastSyncToken): SyncTokenInterface
    {
        if (isset($lastSyncToken['isContinuation'])) {
            return SyncTokenV2::fromArray($lastSyncToken);
        }

        return SyncTokenV1::fromArray($lastSyncToken);
    }
}
