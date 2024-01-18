<?php

namespace App\Controller;

use App\Entity\Kobo;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponseFactory;
use App\Kobo\SyncToken;
use App\Repository\BookRepository;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use App\Service\KoboSyncTokenExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboSyncController extends AbstractController
{
    public const MAX_BOOKS_PER_SYNC = 100;

    public function __construct(
        protected BookRepository $bookRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected KoboSyncTokenExtractor $koboSyncTokenExtractor,
        protected KoboSyncedBookRepository $koboSyncedBookRepository,
        protected ShelfRepository $shelfRepository,
        protected LoggerInterface $logger,
        protected SyncResponseFactory $syncResponseFactory)
    {
    }

    /**
     * Sync library.
     *
     * An HTTP Header is passing the SyncToken option, and we fill also the filter from the get parameters into it.
     * See KoboSyncTokenExtractor and Kobo
     * Both
     * Kobo will call this url multiple times if there are more book to sync (x-kobo-sync: continue)
     * @param Kobo $kobo The kobo entity is retrieved via the accessKey in the url
     * @param SyncToken $syncToken It's provided from HTTP Headers + Get parameters, see SyncTokenParamConverter and    KoboSyncTokenExtractor
     * @return Response
     **/
    #[Route('/v1/library/sync', name: 'api_endpoint_v1_library_sync')]
    public function apiEndpoint(Kobo $kobo, SyncToken $syncToken, Request $request): Response
    {
        if ($kobo->isForceSync() || $request->query->has('force')) {
            $this->koboSyncedBookRepository->deleteAllSyncedBooks($kobo);
            $kobo->setForceSync(false);
        }

        // We fetch a subset of book to sync, based on the SyncToken.
        $books = $this->bookRepository->getChangedBooks($kobo, $syncToken, 0, self::MAX_BOOKS_PER_SYNC);
        $count = $this->bookRepository->getChangedBooksCount($kobo, $syncToken);

        $response = $this->syncResponseFactory->create($syncToken, $kobo)
            ->addBooks($books)
            ->addShelves($this->shelfRepository->getShelvesToSync($kobo, $syncToken));

        // TODO Pagination based on the sync token and lastSyncDate
        $httpResponse = $response->toJsonResponse();
        $httpResponse->headers->set('x-kobo-sync-todo', count($books) < $count ? 'continue' : 'done');

        // Once the response is generated, we update the list of synced books
        // If you do this before, the logic will be broken
        $this->koboSyncedBookRepository->updateSyncedBooks($kobo, $books, $syncToken);

        return $httpResponse;
    }
}
