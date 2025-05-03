<?php

namespace App\Controller\Kobo\Api\V1\Library;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\Response\SyncResponseFactory;
use App\Kobo\SyncToken;
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
     * Sync library.
     *
     * An HTTP Header is passing the SyncToken option, and we fill also the filter from the get parameters into it.
     * See KoboSyncTokenExtractor and Kobo
     * Both
     * Kobo will call this url multiple times if there are more book to sync (x-kobo-sync: continue)
     * @param KoboDevice $koboDevice The kobo entity is retrieved via the accessKey in the url
     * @param ?SyncToken $urlToken It's provided from an HTTP Headers on old firmware. See SyncTokenParamConverter.
     **/
    #[Route('/sync', name: 'api_endpoint_v1_library_sync')]
    public function apiEndpoint(KoboDevice $koboDevice, ?SyncToken $urlToken, Request $request): Response
    {
        $maxBookPerSync = $request->query->getInt('per_page', self::MAX_BOOKS_PER_SYNC);
        $forced = $koboDevice->isForceSync() || $request->query->has('force');

        $syncToken = $urlToken ?? $koboDevice->getLastSyncToken() ?? new SyncToken();
        $syncToken->page = $koboDevice->getLastSyncToken()->page ?? 1;
        $syncToken->tagLastModified ??= $koboDevice->getLastSyncToken()->tagLastModified ?? null;

        if ($forced) {
            $this->koboSyncLogger->debug('Force sync for Kobo {id}', ['id' => $koboDevice->getId()]);
            $this->koboSyncedBookRepository->deleteAllSyncedBooks();
            $koboDevice->setForceSync(false);
            $syncToken = new SyncToken();
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
            'page' => $syncToken->page,
            'count' => $count,
        ]);

        $response = $this->syncResponseFactory->create($syncToken, $koboDevice)
            ->addBooks($books)
            ->addShelves($this->shelfRepository->getShelvesToSync($koboDevice, $syncToken));

        // Fetch the books upstream and merge the answer
        $httpResponse = new JsonResponse();
        [$shouldContinue, $upstreamSyncToken] = $this->upstreamSyncMerger->merge($koboDevice, $response, $request, $httpResponse);
        if ($upstreamSyncToken !== null) {
            $upstreamSyncToken->setPage($syncToken->page);
            $syncToken->override($upstreamSyncToken);
        }
        $shouldContinue = $shouldContinue || count($books) < $count;

        $httpResponse = $response->toJsonResponse($shouldContinue, $httpResponse);
        $this->koboSyncLogger->debug('Synced response', [
            'data' => $response->getData(),
            'continue' => $shouldContinue,
            'upstream-sync-token' => $upstreamSyncToken,
            'page' => $syncToken->page,
            'books' => (new ArrayCollection($books))->map(fn (Book $book) => $book->getTitle())->toArray(),
        ]);

        // Once the response is generated, we update the list of synced books
        $this->koboSyncLogger->debug('Set synced data for {count} books', ['count' => count($books)]);
        $this->koboSyncedBookRepository->updateSyncedBooks($koboDevice, $books, $syncToken);

        // Calculate the new SyncToken value
        $shouldContinue
            ? $syncToken->setPage($syncToken->page + 1)->setTagLastModified(new \DateTimeImmutable('now'))
            : $syncToken->markLastSyncDateAndResetPage();

        $koboDevice->setLastSyncToken($syncToken);
        $this->koboDeviceRepository->save($koboDevice);

        return $httpResponse;
    }
}
