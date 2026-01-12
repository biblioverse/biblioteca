<?php

namespace App\Controller\Kobo\Api\V1\Library;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\Response\SyncResponseFactory;
use App\Kobo\SyncToken\SyncTokenInterface;
use App\Kobo\SyncToken\SyncTokenV1;
use App\Kobo\UpstreamSyncMerger;
use App\Repository\BookRepository;
use App\Repository\KoboDeviceRepository;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}/v1/library', name: 'kobo_')]
class LibraryController extends AbstractKoboController
{
    private const int MAX_BOOKS_PER_SYNC = 100;

    public function __construct(
        protected readonly BookRepository $bookRepository,
        protected readonly KoboSyncedBookRepository $koboSyncedBookRepository,
        protected readonly ShelfRepository $shelfRepository,
        protected readonly LoggerInterface $koboSyncLogger,
        protected readonly KoboDeviceRepository $koboDeviceRepository,
        protected readonly SyncResponseFactory $syncResponseFactory,
        protected readonly UpstreamSyncMerger $upstreamSyncMerger,
    ) {
    }

    /**
     * Synchronize the library.
     *
     * This endpoint is stateful.
     *
     * The KoboDevice may call it multiple times until the sync is complete.
     * A SyncToken, provided by the device in a header, indicates what should be synchronized.
     *
     * An internal page number is used to paginate results — it is not exposed in request headers.
     * The SyncToken and current page are stored in the database to track progress across calls.
     *
     * Once synchronization finishes, the token is updated to the current date and
     * the page number is reset (markLastSyncDateAndResetPage) for the next sync.
     *
     * Note: if "upstream sync" is enabled, the request is forwarded to the real store,
     *  and the results are merged with our local data.
     *
     * @param KoboDevice $koboDevice The kobo entity is retrieved via the accessKey in the url
     * @param ?SyncTokenInterface $urlToken It's provided from an HTTP Headers on old firmware. See SyncTokenParamConverter.
     **/
    #[Route('/sync', name: 'api_endpoint_v1_library_sync')]
    public function apiEndpoint(KoboDevice $koboDevice, ?SyncTokenInterface $urlToken, Request $request): Response
    {
        $maxBookPerSync = $request->query->getInt('per_page', self::MAX_BOOKS_PER_SYNC);
        $forced = $koboDevice->isForceSync() || $request->query->has('force');

        $syncToken = $urlToken ?? $koboDevice->getLastSyncToken() ?? new SyncTokenV1();
        $syncToken->setPage($koboDevice->getLastSyncToken()?->getPage() ?? 1);
        $syncToken->setTagLastModified($syncToken->getTagLastModified() ?? $koboDevice->getLastSyncToken()?->getTagLastModified());

        if ($forced) {
            $this->koboSyncLogger->debug('Force sync for Kobo {id}', ['id' => $koboDevice->getId()]);
            $this->koboSyncedBookRepository->deleteAllSyncedBooks();
            $koboDevice->setForceSync(false);
            $syncToken = new SyncTokenV1();
            $koboDevice->setLastSyncToken($syncToken);
            $this->koboDeviceRepository->save($koboDevice);
        }

        $numberOfAlreadySyncedBooks = $this->koboSyncedBookRepository->countByKoboDevice($koboDevice);
        if ($numberOfAlreadySyncedBooks === 0) {
            $this->koboSyncLogger->debug('First sync for Kobo {id}', ['id' => $koboDevice->getId()]);
        }

        $this->koboSyncLogger->debug('Using sync token', ['token' => $syncToken]);

        // We fetch a subset of book to sync, based on the SyncToken.
        $count = $this->bookRepository->getChangedBooksCount($koboDevice, $syncToken);
        $books = $this->bookRepository->getChangedBooks($koboDevice, $syncToken, 0, $maxBookPerSync);
        $this->koboSyncLogger->debug('Sync for Kobo {id}: {count} books to sync (page {page})', [
            'id' => $koboDevice->getId(),
            'page' => $syncToken->getPage(),
            'count' => $count,
        ]);

        $response = $this->syncResponseFactory->create($syncToken, $koboDevice)
            ->addBooks($books)
            ->addShelves($this->shelfRepository->getShelvesToSync($koboDevice, $syncToken));

        // Fetch the books upstream and merge the answer
        $httpResponse = new JsonResponse();
        [$shouldContinue, $upstreamSyncToken] = $this->upstreamSyncMerger->merge($koboDevice, $response, $request, $httpResponse);
        if ($upstreamSyncToken !== null) {
            $syncToken = $upstreamSyncToken->withPage($syncToken->getPage());
        }
        $shouldContinue = $shouldContinue || count($books) < $count;

        // Calculate the new SyncToken value for next sync (will be sent to the response)
        $shouldContinue
            ? $syncToken->setPage($syncToken->getPage() + 1)->setTagLastModified(new \DateTimeImmutable('now'))
            : $syncToken->markLastSyncDateAndResetPage();

        // Sent the response, with the new token
        $httpResponse = $response->toJsonResponse($shouldContinue, $httpResponse, $syncToken);
        $this->koboSyncLogger->debug('Synced response', [
            'data' => $response->getData(),
            'continue' => $shouldContinue,
            'page' => $syncToken->getPage(),
            'books' => (new ArrayCollection($books))->map(fn (Book $book) => $book->getTitle())->toArray(),
        ]);

        // Once the response is generated, we update the list of synced books
        $this->koboSyncLogger->debug('Set synced data for {count} books', ['count' => count($books)]);
        $this->koboSyncedBookRepository->updateSyncedBooks($koboDevice, $books, $syncToken);

        $koboDevice->setLastSyncToken($syncToken);
        $this->koboDeviceRepository->save($koboDevice);

        return $httpResponse;
    }
}
