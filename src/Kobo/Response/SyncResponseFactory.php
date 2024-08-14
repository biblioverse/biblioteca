<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\SyncToken;
use App\Service\BookProgressionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py
 */
class SyncResponseFactory
{
    public function __construct(
        protected MetadataResponseService $metadataResponseService,
        protected BookProgressionService $bookProgressionService,
        protected SerializerInterface $serializer)
    {
    }

    public function create(SyncToken $syncToken, KoboDevice $kobo): SyncResponse
    {
        return new SyncResponse(
            $this->metadataResponseService,
            $this->bookProgressionService,
            $syncToken,
            $kobo,
            $this->serializer
        );
    }

    public function createMetadata(KoboDevice $kobo, Book $book): JsonResponse
    {
        return new JsonResponse([$this->metadataResponseService->fromBook($book, $kobo)]);
    }
}
