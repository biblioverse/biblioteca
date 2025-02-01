<?php

namespace App\Controller\Kobo\Api\V1\Library;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\KoboDevice;
use App\Kobo\Response\SyncResponseFactory;
use App\Kobo\SyncToken;
use App\Kobo\UpstreamSyncMerger;
use App\Repository\BookRepository;
use App\Repository\KoboDeviceRepository;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}/v1/library', name: 'kobo_')]
class LibraryController extends AbstractKoboController
{
    public const MAX_BOOKS_PER_SYNC = 100;

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
                $syncToken->currentDate = new \DateTimeImmutable('now');
            }
            $this->koboSyncLogger->debug('First sync for Kobo {id}', ['id' => $koboDevice->getId()]);
            $syncToken->lastCreated = null;
            $syncToken->lastModified = null;
            $syncToken->tagLastModified = null;
            $syncToken->archiveLastModified = null;
        }

        $maxBookPerSync = $request->query->getInt('per_page', self::MAX_BOOKS_PER_SYNC);
        // We fetch a subset of book to sync, based on the SyncToken.
        $books = $this->bookRepository->getChangedBooks($koboDevice, $syncToken, 0, $maxBookPerSync);
        $count = $this->bookRepository->getChangedBooksCount($koboDevice, $syncToken);
        $this->koboSyncLogger->debug("Sync for Kobo {id}: {$count} books to sync", ['id' => $koboDevice->getId(), 'count' => $count, 'token' => $syncToken]);

        $response = $this->syncResponseFactory->create($syncToken, $koboDevice)
            ->addBooks($books)
            ->addShelves($this->shelfRepository->getShelvesToSync($koboDevice, $syncToken));

        // Fetch the books upstream and merge the answer
        $shouldContinue = $this->upstreamSyncMerger->merge($koboDevice, $response, $request);

        $httpResponse = $response->toJsonResponse();
        $httpResponse->headers->set(KoboDevice::KOBO_SYNC_SHOULD_CONTINUE_HEADER, $shouldContinue || count($books) < $count ? 'continue' : 'done');

        // Once the response is generated, we update the list of synced books
        // If you do this before, the logic will be broken
        $this->koboSyncLogger->debug('Set synced date for {count} downloaded books', ['count' => count($books)]);

        $this->koboSyncedBookRepository->updateSyncedBooks($koboDevice, $books, $syncToken);

        return $httpResponse;
    }
}
