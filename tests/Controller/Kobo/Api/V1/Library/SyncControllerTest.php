<?php

namespace App\Tests\Controller\Kobo\Api\V1\Library;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Entity\Shelf;
use App\Kobo\SyncToken;
use App\Repository\BookRepository;
use App\Service\KoboSyncTokenExtractor;
use App\Tests\Contraints\JSONIsValidSyncResponse;
use App\Tests\Controller\Kobo\KoboControllerTestCase;
use App\Tests\TestClock;

class SyncControllerTest extends KoboControllerTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->getKoboStoreProxy()->setClient(null);
        $this->getKoboProxyConfiguration()->setEnabled(false);
        $this->getKoboDevice()->setLastSyncToken(null);
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks($this->getKoboDevice());
        $this->getEntityManager()->getRepository(BookInteraction::class)->createQueryBuilder('bi')->delete()->getQuery()->execute();
        $this->changeAllBooksDate(new \DateTimeImmutable('2025-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->changeAllShelvesDate(new \DateTimeImmutable('2025-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->getEntityManager()->flush();
        $this->getService(TestClock::class)->setTime(null); // Keep after the flush for gedmo.
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSyncControllerWithForce(): void
    {
        $client = static::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync?force=1');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_OWNED_YAML_BOOKS,
            'NewTag' => 1,
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'Number of synced book is invalid');
    }

    public function testSyncControllerWithoutForce(): void
    {
        $client = static::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_OWNED_YAML_BOOKS,
            'NewTag' => 1,
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'Number of synced book is invalid');
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerPaginatedWithSyncToken(): void
    {
        $client = static::getClient();

        $perPage = 7;
        $numberOfPages = (int) ceil(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS / $perPage);

        $syncToken = new SyncToken();
        $syncToken->lastCreated = new \DateTimeImmutable('now');
        // Build the sync-token header
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

        $count = $this->getService(BookRepository::class)->getChangedBooksCount($this->getKoboDevice(), $syncToken);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'We expect to have 20 books to sync');

        foreach (range(1, $numberOfPages) as $pageNum) {
            $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync?per_page='.$perPage, [], [], $headers);

            $response = self::getJsonResponse();
            self::assertResponseIsSuccessful();

            // We have 20 books, with 7 book per page, we do 3 calls that have respectively 7, 7 and 6 books
            self::assertThat($response, new JSONIsValidSyncResponse(match ($pageNum) {
                1 => [
                    'NewTag' => 1,
                    'NewEntitlement' => 7,
                ],
                2 => [
                    'NewEntitlement' => 7,
                ],
                3 => [
                    'NewEntitlement' => 6,
                ],
                default => [],
            }, $pageNum), 'Response is not a valid sync response for page '.$pageNum);

            $expectedContinueHeader = $pageNum === $numberOfPages ? 'done' : 'continue';
            self::assertResponseHeaderSame(KoboDevice::KOBO_SYNC_SHOULD_CONTINUE_HEADER, $expectedContinueHeader, 'x-kobo-sync is invalid');
        }

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'Number of synced book is invalid');
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerPaginatedWithoutSyncToken(): void
    {
        $client = static::getClient();

        $perPage = 7;
        $numberOfPages = (int) ceil(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS / $perPage);

        $syncToken = new SyncToken();
        $syncToken->lastCreated = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $syncToken->lastModified = null;
        $this->getKoboDevice()->setLastSyncToken($syncToken);
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $this->getShelf()->setCreated($today->modify('-1 day'));
        $this->getEntityManager()->flush();

        $count = $this->getService(BookRepository::class)->getChangedBooksCount($this->getKoboDevice(), $syncToken);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'We expect to have 20 books to sync');

        foreach (range(1, $numberOfPages) as $pageNum) {
            $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync?per_page='.$perPage);

            $response = self::getJsonResponse();
            self::assertResponseIsSuccessful();

            // We have 20 books, with 7 book per page, we do 3 calls that have respectively 7, 7 and 6 books

            self::assertThat($response, new JSONIsValidSyncResponse(match ($pageNum) {
                1 => [
                    'NewTag' => 1,
                    'NewEntitlement' => 7,
                ],
                2 => [
                    'NewEntitlement' => 7,
                ],
                3 => [
                    'NewEntitlement' => 6,
                ],
                default => [],
            }, $pageNum), 'Response is not a valid sync response for page '.$pageNum);

            $expectedContinueHeader = $pageNum === $numberOfPages ? 'done' : 'continue';
            self::assertResponseHeaderSame(KoboDevice::KOBO_SYNC_SHOULD_CONTINUE_HEADER, $expectedContinueHeader, 'x-kobo-sync is invalid');
        }

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS, $count, 'Number of synced book is invalid');

        // Calling one more time should have an empty result
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync?per_page='.$perPage, [], [], $headers);
        self::assertResponseIsSuccessful();
        self::assertThat(self::getJsonResponse(), new JSONIsValidSyncResponse([], $numberOfPages + 1));
    }

    /**
     * Syncing book multiple times should not change the number of synced books.
     */
    public function testSyncControllerSyncedBookCount(): void
    {
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(0, $count, 'Number of synced book is invalid');

        $client = static::getClient();
        $syncToken = new SyncToken();
        $syncToken->lastModified = new \DateTimeImmutable('now');

        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertGreaterThan(0, $count, 'Number of synced book is invalid');

        // Edit a book to force it to be synced again
        $this->getBook()->setUpdated(new \DateTimeImmutable('+1 day'));
        $this->getEntityManager()->flush();

        // Same query a second time, the amount of synced-books must be the same.
        $syncToken->lastModified = new \DateTimeImmutable('now');
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        $count2 = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame($count, $count2, 'Number of synced book should not change');
    }

    /**
     * @throws \JsonException
     * @throws \DateMalformedStringException
     */
    public function testSyncControllerEdited(): void
    {
        $client = static::getClient();
        $clock = $this->getService(TestClock::class);
        $book = $this->getBook();
        $book->setCreated($clock->now());
        $book->setUpdated($clock->now());

        $this->markAllBooksAsSynced($clock->now());

        $clock->alter('+ 10 seconds');

        // Create a sync token
        $syncToken = new SyncToken();
        $syncToken->lastCreated = $clock->now();
        $syncToken->lastModified = $clock->now();
        $syncToken->tagLastModified = $clock->now();

        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

        // Edit the book detail
        $clock->alter('+ 10 seconds');
        $book->setUpdated($clock->now());
        $this->getEntityManager()->flush();
        $clock->setTime(null);

        // Make sure the book has changed.
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        self::assertResponseIsSuccessful();
        self::assertThat(self::getJsonResponse(), new JSONIsValidSyncResponse([
            'ChangedEntitlement' => 1,
        ]));

        self::assertResponseHeaderSame(KoboDevice::KOBO_SYNC_SHOULD_CONTINUE_HEADER, 'done', 'x-kobo-sync is invalid');
    }

    public function testSyncControllerWithRemote(): void
    {
        $client = static::getClient();

        $this->enableRemoteSync();

        $this->getKoboStoreProxy()->setClient($this->getMockClient('[{
                "DeletedTag": {
                    "Tag": {
                        "Id": "28521096-ed64-4709-a043-781a0ed0695f",
                        "LastModified": "2024-02-02T13:35:31.0000000Z"
                    }
                }
            }]'));

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_OWNED_YAML_BOOKS,
            'NewTag' => 1,
            'DeletedTag' => 1,
        ]), 'Response is not a valid sync response');

        $this->getKoboDevice()->setUpstreamSync(false);
        $this->getEntityManager()->flush();
    }

    public function testArchivedBookSync(): void
    {
        $client = static::getClient();

        $clock = $this->getService(TestClock::class);
        $clock->setTime(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));

        // Mark all books as synced
        $this->markAllBooksAsSynced($clock->now());
        $clock->setTime(null);

        $syncedBook = $this->getEntityManager()->getRepository(KoboSyncedBook::class)
            ->findOneBy(['koboDevice' => 1, 'book' => $this->getBook()]);
        self::assertNotNull($syncedBook, 'You should have the book marked as synced');

        // Mark one book as removed from kobo/shelf
        $archivedDate = new \DateTimeImmutable('2025-01-31 22:00:00', new \DateTimeZone('UTC'));
        $syncedBook->setArchived($archivedDate);
        $this->getEntityManager()->flush();

        // Make sure the book is there with the archived flag
        $syncToken = new SyncToken();
        $syncToken->archiveLastModified = new \DateTimeImmutable('2025-01-31 19:00:00', new \DateTimeZone('UTC'));
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        $response = self::getJsonResponse();

        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'ChangedEntitlement' => 1,
            'NewTag' => 1,
        ]), 'Response is not a valid sync response');

        $removed = $response[0]['ChangedEntitlement']['BookEntitlement']['IsRemoved'] ?? null;
        self::assertTrue($removed, 'The book should be marked as removed');

        $syncedBook = $this->getEntityManager()->getRepository(KoboSyncedBook::class)
            ->findOneBy(['koboDevice' => 1, 'book' => $this->getBook()]);
        self::assertNull($syncedBook, 'The syncedBook should be removed');
    }

    private function markAllBooksAsSynced(\DateTimeImmutable $when): void
    {
        $em = $this->getEntityManager();
        $books = $this->getService(BookRepository::class)->findAll();

        $koboDevice = $this->getKoboDevice();
        foreach ($books as $book) {
            $syncedBook = new KoboSyncedBook($when, $when, $koboDevice, $book);
            $book->addKoboSyncedBook($syncedBook);
            $em->persist($syncedBook);
        }
        $em->flush();
    }

    private function changeAllBooksDate(\DateTimeImmutable $when): void
    {
        // It seems that Gedmo take over, so we tells him the date too
        (new TestClock())->setTime($when);
        $this->getEntityManager()->createQueryBuilder()
            ->update(Book::class, 'b')
            ->set('b.updated', ':when')
            ->set('b.created', ':when')
            ->where('b.id > 0')
            ->setParameter('when', $when)
            ->getQuery()
            ->execute();
    }

    private function changeAllShelvesDate(\DateTimeImmutable $when): void
    {
        // It seems that Gedmo take over, so we tells him the date too
        (new TestClock())->setTime($when);
        $this->getEntityManager()->createQueryBuilder()
            ->update(Shelf::class, 's')
            ->set('s.updated', ':when')
            ->set('s.created', ':when')
            ->orWhere('s.id > 0')
            ->setParameter('when', $when)
            ->getQuery()
            ->execute();
    }
}
