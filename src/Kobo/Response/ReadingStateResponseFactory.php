<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\SyncToken;
use App\Service\BookProgressionService;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py
 */
class ReadingStateResponseFactory
{
    public function __construct(
        protected MetadataResponseService $metadataResponseService,
        protected BookProgressionService $bookProgressionService,
        protected SerializerInterface $serializer)
    {
    }

    public function create(SyncToken $syncToken, KoboDevice $kobo, Book $book): ReadingStateResponse
    {
        return new ReadingStateResponse(
            $this->bookProgressionService,
            $this->serializer,
            $syncToken,
            $kobo,
            $book
        );
    }
}
