<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\Entity\KoboSyncedBook;
use App\Kobo\Response\MetadataResponseService;
use App\Kobo\SyncToken;
use App\Repository\KoboSyncedBookRepository;
use App\Service\KoboSyncTokenExtractor;
use App\Tests\Contraints\AssertHasDownloadWithFormat;
use App\Tests\Contraints\JSONIsValidSyncResponse;

class KoboSyncControllerTest extends AbstractKoboControllerTest
{
    protected function tearDown(): void
    {
        $this->getKoboStoreProxy()->setClient(null);
        $this->getKoboProxyConfiguration()->setEnabled(false);
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);

        parent::tearDown();
    }

    protected function setUp():void
    {
        parent::setUp();

        $this->getService(KoboSyncedBookRepository::class)->deleteAllSyncedBooks(1);
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerWithForce() : void
    {
        $client = static::getClient();
        

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync?force=1');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);


        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_YAML_BOOKS,
            'NewTag' => 1
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(0, $count, 'There should be no synced books');
    }

    public function testSyncControllerWithoutForce() : void
    {
        $client = static::getClient();
        

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_YAML_BOOKS,
            'NewTag' => 1
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_YAML_BOOKS, $count, 'Number of synced book is invalid');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);

    }

    /**
     * @covers pagination
     * @throws \JsonException
     */
    public function testSyncControllerPaginated() : void
    {
        $client = static::getClient();
        

        $perPage = 7;
        $numberOfPages = (int)ceil(BookFixture::NUMBER_OF_YAML_BOOKS / $perPage);

        $syncToken = new SyncToken();
        $syncToken->lastCreated = new \DateTime('now');
        $syncToken->lastModified = null;

        foreach(range(1, $numberOfPages) as $pageNum) {
            // Build the sync-token header
            $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

            $client?->request('GET', '/kobo/' . $this->accessKey . '/v1/library/sync?per_page=' . $perPage, [], [], $headers);

            $response = self::getJsonResponse();
            self::assertResponseIsSuccessful();

            // We have 20 books, with 7 book per page, we do 3 calls that have respectively 7, 7 and 6 books
            self::assertThat($response, new JSONIsValidSyncResponse(match($pageNum){
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
            self::assertResponseHeaderSame('x-kobo-sync', $expectedContinueHeader, 'x-kobo-sync is invalid');
        }

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(BookFixture::NUMBER_OF_YAML_BOOKS, $count, 'Number of synced book is invalid');

        // Calling one more time should have an empty result
        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);
        $client?->request('GET', '/kobo/' . $this->accessKey . '/v1/library/sync?per_page=' . $perPage, [], [], $headers);
        self::assertResponseIsSuccessful();
        self::assertThat(self::getJsonResponse(), new JSONIsValidSyncResponse([], $numberOfPages+1));


    }

    public function testSyncControllerWithRemote() : void
    {
        $client = static::getClient();

        // Enable remote sync
        $this->getKoboDevice()->setUpstreamSync(true);
        $this->getKoboProxyConfiguration()->setEnabled(true);
        $this->getEntityManager()->flush();
        $this->getKoboDevice(true);

        $this->getKoboStoreProxy()->setClient($this->getMockClient('[{
                "DeletedTag": {
                    "Tag": {
                        "Id": "28521096-ed64-4709-a043-781a0ed0695f",
                        "LastModified": "2024-02-02T13:35:31.0000000Z"
                    }
                }
            }]'));


        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => BookFixture::NUMBER_OF_YAML_BOOKS,
            'NewTag' => 1,
            'DeletedTag' => 1
        ]), 'Response is not a valid sync response');


        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
        $this->getKoboDevice()->setUpstreamSync(false);
        $this->getEntityManager()->flush();
    }

    public function testSyncControllerMetadata() : void
    {
        $uuid = $this->getBook()->getUuid();
        $client = static::getClient();
        $this->getKepubifyEnabler()->disable();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/'.$uuid."/metadata");

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new AssertHasDownloadWithFormat(MetadataResponseService::EPUB3_FORMAT), 'Response is not a valid download response');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
    }

    public function testSyncControllerMetadataWithConversion() : void
    {
        $uuid = $this->getBook()->getUuid();
        $client = static::getClient();
        self::assertTrue($this->getKepubifyEnabler()->isEnabled());

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/'.$uuid."/metadata");

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new AssertHasDownloadWithFormat(MetadataResponseService::KEPUB_FORMAT), 'Response is not a valid download response');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
    }
}