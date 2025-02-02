<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\SyncToken;
use App\Kobo\SyncTokenParser;
use App\Service\BookProgressionService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py
 */
readonly class SyncResponseFactory
{
    public function __construct(
        protected MetadataResponseService $metadataResponseService,
        protected BookProgressionService $bookProgressionService,
        protected SerializerInterface $serializer,
        protected ReadingStateResponseFactory $readingStateResponseFactory,
        protected SyncTokenParser $syncTokenParser,
        #[Autowire('%kernel.debug')]
        protected bool $kernelDebug,
    ) {
    }

    public function create(SyncToken $syncToken, KoboDevice $koboDevice): SyncResponse
    {
        return new SyncResponse(
            $this->metadataResponseService,
            $this->bookProgressionService,
            $syncToken,
            $koboDevice,
            $this->serializer,
            $this->readingStateResponseFactory,
            $this->syncTokenParser,
            $this->kernelDebug
        );
    }

    public function createMetadata(KoboDevice $koboDevice, Book $book): JsonResponse
    {
        return new JsonResponse([$this->metadataResponseService->fromBook($book, $koboDevice)]);
    }
}
