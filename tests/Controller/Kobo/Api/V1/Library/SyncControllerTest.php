<?php

namespace App\Tests\Controller\Kobo\Api\V1\Library;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Kobo\SyncToken;
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
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->getKoboStoreProxy()->setClient(null);
        $this->getKoboProxyConfiguration()->setEnabled(false);
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
        $this->getService(TestClock::class)->setTime(null);
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
    public function testSyncControllerPaginated(): void
    {
        $client = static::getClient();

        $perPage = 7;
        $numberOfPages = (int) ceil(BookFixture::NUMBER_OF_OWNED_YAML_BOOKS / $perPage);

        $syncToken = new SyncToken();
        $syncToken->lastCreated = new \DateTime('now');
        $syncToken->lastModified = null;

        foreach (range(1, $numberOfPages) as $pageNum) {
            // Build the sync-token header
            $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

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
        $syncToken->lastModified = new \DateTime('now');

        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertGreaterThan(0, $count, 'Number of synced book is invalid');

        // Edit a book to force it to be synced again
        $this->getBook()->setUpdated(new \DateTime('+1 day'));
        $this->getEntityManager()->flush();

        // Same query a second time, the amount of synced-books must be the same.
        $syncToken->lastModified = new \DateTime('now');
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

        // Create an old sync token
        $clock = $this->getService(TestClock::class)
            ->setTime(new \DateTimeImmutable('now'));
        $syncToken = new SyncToken();
        $syncToken->lastCreated = $clock->now();
        $syncToken->lastModified = $clock->now();
        $syncToken->tagLastModified = $clock->now();

        // Sync all the books
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);
        self::assertResponseIsSuccessful();

        // Edit the book detail
        $clock->setTime($clock->now()->modify('+ 1 hour'));
        $book = $this->getBook();
        $slug = $book->getSlug();
        $book->setSlug($slug.'..');
        $this->getEntityManager()->flush();
        $book->setSlug($slug);
        $this->getEntityManager()->flush();

        // Restore the real time
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
}
