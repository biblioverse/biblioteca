<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\BookmarkUser;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\ReadingStateResponseFactory;
use App\Kobo\Response\StateResponse;
use App\Kobo\Response\SyncResponseFactory;
use App\Kobo\SyncToken;
use App\Kobo\UpstreamSyncMerger;
use App\Repository\BookRepository;
use App\Repository\KoboDeviceRepository;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use App\Service\BookProgressionService;
use App\Service\KoboSyncTokenExtractor;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}/v1/library', name: 'kobo_')]
class LibraryController extends AbstractKoboController
{
    public const MAX_BOOKS_PER_SYNC = 100;

    public function __construct(
        protected BookRepository $bookRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected EntityManagerInterface $em,
        protected BookProgressionService $bookProgressionService,
        protected ReadingStateResponseFactory $readingStateResponseFactory,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected KoboSyncTokenExtractor $koboSyncTokenExtractor,
        protected KoboSyncedBookRepository $koboSyncedBookRepository,
        protected ShelfRepository $shelfRepository,
        protected LoggerInterface $koboSyncLogger,
        protected KoboDeviceRepository $koboDeviceRepository,
        protected SyncResponseFactory $syncResponseFactory,
        protected UpstreamSyncMerger $upstreamSyncMerger,
    ) {
    }

    /**
     * Update reading state.
     **/
    #[Route('/{uuid}/state', name: 'api_endpoint_state_put', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['PUT'])]
    public function putState(KoboDevice $koboDevice, string $uuid, Request $request): Response|JsonResponse
    {
        $book = $this->bookRepository->findByUuidAndKoboDevice($uuid, $koboDevice);

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
    public function getState(KoboDevice $koboDevice, string $uuid, Request $request, SyncToken $syncToken): Response|JsonResponse
    {
        // Get State returns an empty response
        $response = new JsonResponse([]);
        $response->headers->set('x-kobo-api-token', 'e30=');

        $book = $this->bookRepository->findByUuidAndKoboDevice($uuid, $koboDevice);

        // Unknown book
        if (!$book instanceof Book) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxyOrRedirect($request);
            }
            $response->setData(['error' => 'Book not found']);

            return $response->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
        }

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
        $this->em->persist($bookmark);

        $bookmark->setPercent($currentBookmark->progressPercent === null ? null : $currentBookmark->progressPercent / 100);
        $bookmark->setLocationType($currentBookmark->location?->type);
        $bookmark->setLocationSource($currentBookmark->location?->source);
        $bookmark->setLocationValue($currentBookmark->location?->value);
        $bookmark->setSourcePercent($currentBookmark->contentSourceProgressPercent === null ? null : $currentBookmark->contentSourceProgressPercent / 100);
    }

    /**
     * Sync library.
     *
     * An HTTP Header is passing the SyncToken option, and we fill also the filter from the get parameters into it.
     * See KoboSyncTokenExtractor and Kobo
     * Both
     * Kobo will call this url multiple times if there are more book to sync (x-kobo-sync: continue)
     * @param KoboDevice $koboDevice The kobo entity is retrieved via the accessKey in the url
     * @param SyncToken $syncToken It's provided from HTTP Headers + Get parameters, see SyncTokenParamConverter and    KoboSyncTokenExtractor
     **/
    #[Route('/sync', name: 'api_endpoint_v1_library_sync')]
    public function apiEndpoint(KoboDevice $koboDevice, SyncToken $syncToken, Request $request): Response
    {
        $forced = $koboDevice->isForceSync() || $request->query->has('force');
        $count = $this->koboSyncedBookRepository->countByKoboDevice($koboDevice);
        if ($forced || $count === 0) {
            if ($forced) {
                $this->koboSyncLogger->debug('Force sync for Kobo {id}', ['id' => $koboDevice->getId()]);
                $this->koboSyncedBookRepository->deleteAllSyncedBooks($koboDevice);
                $koboDevice->setForceSync(false);
                $this->koboDeviceRepository->save($koboDevice);
                $syncToken->currentDate = new \DateTime('now');
            }
            $this->koboSyncLogger->debug('First sync for Kobo {id}', ['id' => $koboDevice->getId()]);
            $syncToken->lastCreated = null;
            $syncToken->lastModified = null;
            $syncToken->tagLastModified = null;
            $syncToken->archiveLastModified = null;
        }

        // We fetch a subset of book to sync, based on the SyncToken.
        $books = $this->bookRepository->getChangedBooks($koboDevice, $syncToken, 0, self::MAX_BOOKS_PER_SYNC);
        $count = $this->bookRepository->getChangedBooksCount($koboDevice, $syncToken);
        $this->koboSyncLogger->debug("Sync for Kobo {id}: {$count} books to sync", ['id' => $koboDevice->getId(), 'count' => $count, 'token' => $syncToken]);

        $response = $this->syncResponseFactory->create($syncToken, $koboDevice)
            ->addBooks($books)
            ->addShelves($this->shelfRepository->getShelvesToSync($koboDevice, $syncToken));

        // Fetch the books upstream and merge the answer
        $shouldContinue = $this->upstreamSyncMerger->merge($koboDevice, $response, $request);

        // TODO Pagination based on the sync token and lastSyncDate
        $httpResponse = $response->toJsonResponse();
        $httpResponse->headers->set('x-kobo-sync-todo', $shouldContinue || count($books) < $count ? 'continue' : 'done');

        // Once the response is generated, we update the list of synced books
        // If you do this before, the logic will be broken
        if (false === $forced) {
            $this->koboSyncLogger->debug('Set synced date for {count} downloaded books', ['count' => count($books)]);

            $this->koboSyncedBookRepository->updateSyncedBooks($koboDevice, $books, $syncToken);
        }

        return $httpResponse;
    }

    #[Route('/{uuid}/metadata', name: 'api_endpoint_v1_library_metadata')]
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
