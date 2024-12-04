<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\Entity\KoboSyncedBook;
use App\Kobo\Response\MetadataResponseService;
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
    public function assertPreConditions(): void
    {
        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(0, $count, 'There should be no synced books');
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerWithForce() : void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();

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
        $this->injectFakeFileSystemManager();

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

        $this->injectFakeFileSystemManager();

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
        $this->injectFakeFileSystemManager();
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
        $this->injectFakeFileSystemManager();
        self::assertTrue($this->getKepubifyEnabler()->isEnabled());

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/'.$uuid."/metadata");

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new AssertHasDownloadWithFormat(MetadataResponseService::KEPUB_FORMAT), 'Response is not a valid download response');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
    }
}