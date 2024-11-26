<?php

namespace App\Tests\Controller\Kobo;

use App\Entity\KoboSyncedBook;
use App\Kobo\Response\MetadataResponseService;
use App\Tests\Contraints\AssertHasDownloadWithFormat;
use App\Tests\Contraints\JSONIsValidSyncResponse;

class KoboSyncControllerTest extends AbstractKoboControllerTest
{

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

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync?force=1');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);


        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => 1,
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
            'NewEntitlement' => 1,
            'NewTag' => 1
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['koboDevice' => 1]);
        self::assertSame(1, $count, 'There should be 1 synced books');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);

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