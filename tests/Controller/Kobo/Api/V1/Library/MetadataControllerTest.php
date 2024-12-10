<?php

namespace App\Tests\Controller\Kobo\Api\V1\Library;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\KoboSyncedBook;
use App\Kobo\Response\MetadataResponseService;
use App\Tests\Contraints\AssertHasDownloadWithFormat;
use App\Tests\Controller\Kobo\AbstractKoboControllerTest;

class MetadataControllerTest extends AbstractKoboControllerTest
{
    public function testSyncControllerMetadata(): void
    {
        $uuid = $this->getBook()->getUuid();
        $client = static::getClient();
        $this->getKepubifyEnabler()->disable();

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/'.$uuid.'/metadata');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new AssertHasDownloadWithFormat(MetadataResponseService::EPUB3_FORMAT), 'Response is not a valid download response');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerMetadataWithConversion(): void
    {
        $uuid = $this->getBook()->getUuid();
        $client = static::getClient();
        self::assertTrue($this->getKepubifyEnabler()->isEnabled());

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/'.$uuid.'/metadata');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new AssertHasDownloadWithFormat(MetadataResponseService::KEPUB_FORMAT), 'Response is not a valid download response');
    }

    public function testSyncControllerMetadataWithProxy(): void
    {
        $unknownUuid = str_replace('0', 'b', BookFixture::UUID_JUNGLE_BOOK);
        $this->getKoboStoreProxy()->setClient($this->getMockClient('-- fake result --'));
        $this->enableRemoteSync();

        $client = static::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/'.$unknownUuid.'/metadata');

        self::assertResponseIsSuccessful();
    }
}
