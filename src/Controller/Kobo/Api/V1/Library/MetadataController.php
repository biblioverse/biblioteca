<?php

namespace App\Controller\Kobo\Api\V1\Library;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetadataController extends AbstractKoboController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
        protected SyncResponseFactory $syncResponseFactory,
    ) {
    }

    #[Route('/kobo/{accessKey:koboDevice}/v1/library/{uuid:book}/metadata', name: 'api_endpoint_v1_library_metadata')]
    public function metadataEndpoint(KoboDevice $koboDevice, ?Book $book, Request $request): Response
    {
        if (!$book instanceof Book) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxy($request);
            }

            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->syncResponseFactory->createMetadata($koboDevice, $book);
    }
}
