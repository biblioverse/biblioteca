<?php

namespace App\Kobo\Response;

use App\Entity\Kobo;
use App\Kobo\SyncToken;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py
 */
class SyncResponseFactory
{
    public function __construct(protected MetadataResponseService $metadataResponseService, protected SerializerInterface $serializer)
    {
    }

    public function create(SyncToken $syncToken, Kobo $kobo): SyncResponse
    {
        return new SyncResponse($this->metadataResponseService, $syncToken, $kobo, $this->serializer);
    }
}
