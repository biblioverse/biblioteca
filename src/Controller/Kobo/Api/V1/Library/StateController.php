<?php

namespace App\Controller\Kobo\Api\V1\Library;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\BookmarkUser;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\ReadingStateResponseFactory;
use App\Kobo\Response\StateResponse;
use App\Kobo\SyncToken\SyncTokenInterface;
use App\Kobo\SyncToken\SyncTokenV1;
use App\Repository\BookRepository;
use App\Security\Voter\BookVoter;
use App\Service\BookProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}/v1/library', name: 'kobo_')]
class StateController extends AbstractKoboController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected EntityManagerInterface $em,
        protected BookProgressionService $bookProgressionService,
        protected ReadingStateResponseFactory $readingStateResponseFactory,
    ) {
    }

    /**
     * Update reading state.
     **/
    #[Route('/{uuid}/state', name: 'api_endpoint_state_put', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['PUT'])]
    public function putState(KoboDevice $koboDevice, string $uuid, Request $request): Response|JsonResponse
    {
        $book = $this->bookRepository->findByUuid($uuid);

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
                $this->bookProgressionService->setProgression($book, $koboDevice->getUser(), 1.0);
                break;
            case ReadingStateStatusInfo::STATUS_READY_TO_READ:
                $this->bookProgressionService->setProgression($book, $koboDevice->getUser(), null);
                break;
            case ReadingStateStatusInfo::STATUS_READING:
                $progress = $state->currentBookmark?->progressPercent;
                $progress = $progress !== null ? $progress / 100 : null;
                $this->bookProgressionService->setProgression($book, $koboDevice->getUser(), $progress);
                break;
            case null:
                break;
        }

        $this->handleBookmark($koboDevice, $book, $state->currentBookmark);

        $this->em->flush();

        return new StateResponse($book);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/{uuid}/state', name: 'api_endpoint_v1_getstate', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET'])]
    public function getState(KoboDevice $koboDevice, string $uuid, Request $request, ?SyncTokenInterface $syncToken = null): Response|JsonResponse
    {
        // Get State returns an empty response
        $response = new JsonResponse([]);
        $response->headers->set(KoboDevice::KOBO_API_TOKEN, KoboDevice::KOBO_API_TOKEN_VALUE);

        $book = $this->bookRepository->findByUuid($uuid);

        // Unknown book
        if (!$book instanceof Book) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxyOrRedirect($request);
            }
            $response->setData(['error' => 'Book not found']);

            return $response->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
        }

        $this->denyAccessUnlessGranted(BookVoter::VIEW, $book, 'You are not allowed to view this book');

        $syncToken = $syncToken ?? $koboDevice->getLastSyncToken() ?? new SyncTokenV1();
        $rsResponse = $this->readingStateResponseFactory->create($syncToken, $koboDevice, $book);

        $response->setContent($rsResponse);

        return $response;
    }

    private function handleBookmark(KoboDevice $koboDevice, Book $book, ?Bookmark $currentBookmark): void
    {
        if (!$currentBookmark instanceof Bookmark) {
            $koboDevice->getUser()->removeBookmarkForBook($book);

            return;
        }

        $bookmark = $koboDevice->getUser()->getBookmarkForBook($book) ?? new BookmarkUser($book, $koboDevice->getUser());
        $bookmark->setBook($book);
        $koboDevice->getUser()->addBookmarkUser($bookmark);
        $this->em->persist($bookmark);

        $bookmark->setPercent($currentBookmark->progressPercent === null ? null : $currentBookmark->progressPercent / 100);
        $bookmark->setLocationType($currentBookmark->location?->type);
        $bookmark->setLocationSource($currentBookmark->location?->source);
        $bookmark->setLocationValue($currentBookmark->location?->value);
        $bookmark->setSourcePercent($currentBookmark->contentSourceProgressPercent === null ? null : $currentBookmark->contentSourceProgressPercent / 100);
    }
}
