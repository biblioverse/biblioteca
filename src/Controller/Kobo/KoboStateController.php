<?php

namespace App\Controller\Kobo;

use App\Entity\Book;
use App\Entity\BookmarkUser;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\StateResponse;
use App\Repository\BookRepository;
use App\Service\BookProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboStateController extends AbstractController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected EntityManagerInterface $em,
        protected BookProgressionService $bookProgressionService,
    ) {
    }

    /**
     * Update reading state.
     **/
    #[Route('/v1/library/{uuid}/state', name: 'api_endpoint_state_put', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['PUT'])]
    public function state(KoboDevice $kobo, string $uuid, Request $request): Response|JsonResponse
    {
        $book = $this->bookRepository->findByUuidAndKoboDevice($uuid, $kobo);

        // Handle book not found
        if (!$book instanceof Book) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxy($request);
            }

            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        // Deserialize request
        /** @var ReadingStates $entity */
        $entity = $this->serializer->deserialize($request->getContent(), ReadingStates::class, 'json');

        if (count($entity->readingStates) === 0) {
            return new JsonResponse(['error' => 'No reading state provided'], Response::HTTP_BAD_REQUEST);
        }
        $state = $entity->readingStates[0];
        switch ($state->statusInfo?->status) {
            case ReadingStateStatusInfo::STATUS_FINISHED:
                $this->bookProgressionService->setProgression($book, $kobo->getUser(), 1.0);
                break;
            case ReadingStateStatusInfo::STATUS_READY_TO_READ:
                $this->bookProgressionService->setProgression($book, $kobo->getUser(), null);
                break;
            case ReadingStateStatusInfo::STATUS_READING:
                $progress = $state->currentBookmark?->progressPercent;
                $progress = $progress !== null ? $progress / 100 : null;
                $this->bookProgressionService->setProgression($book, $kobo->getUser(), $progress);
                break;
            case null:
                break;
        }

        $this->handleBookmark($kobo, $book, $state->currentBookmark);

        $this->em->flush();

        return new StateResponse($book);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/v1/library/{uuid}/state', name: 'api_endpoint_v1_getstate', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET'])]
    public function getState(KoboDevice $kobo, string $uuid, Request $request): Response|JsonResponse
    {
        // Get State returns an empty response
        $response = new JsonResponse([]);
        $response->headers->set('x-kobo-api-token', 'e30=');

        $book = $this->bookRepository->findByUuidAndKoboDevice($uuid, $kobo);

        // Empty response if we know the book
        if ($book instanceof Book) {
            return $response;
        }

        // If we do not know the book, we forward the query to the proxy
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }

        return $response->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
    }

    private function handleBookmark(KoboDevice $kobo, Book $book, ?Bookmark $currentBookmark): void
    {
        if (!$currentBookmark instanceof Bookmark) {
            $kobo->getUser()->removeBookmarkForBook($book);

            return;
        }

        $bookmark = $kobo->getUser()->getBookmarkForBook($book) ?? new BookmarkUser($book, $kobo->getUser());
        $this->em->persist($bookmark);

        $bookmark->setPercent($currentBookmark->progressPercent === null ? null : $currentBookmark->progressPercent / 100);
        $bookmark->setLocationType($currentBookmark->location?->type);
        $bookmark->setLocationSource($currentBookmark->location?->source);
        $bookmark->setLocationValue($currentBookmark->location?->value);
        $bookmark->setSourcePercent($currentBookmark->contentSourceProgressPercent === null ? null : $currentBookmark->contentSourceProgressPercent / 100);
    }
}
