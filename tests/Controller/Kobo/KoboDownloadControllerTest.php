<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\Kepubify\KepubifyEnabler;
use App\Kobo\Response\MetadataResponseService;

class KoboDownloadControllerTest extends AbstractKoboControllerTest
{
    public function testDownload(): void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();

        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

        $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.%s', $this->accessKey, BookFixture::ID, 'epub'));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/epub+zip');
        self::assertResponseHasHeader('Content-Length');
        $expectedDisposition = "attachment; filename=book-1-TheOdysses.epub; filename*=utf-8''TheOdysses.epub";
        self::assertResponseHeaderSame('Content-Disposition', $expectedDisposition, 'The Content-Disposition header is not as expected');

    }

    public function testDownloadKepubFailed(): void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();

        // Disable Kepubify conversion
        $lastValue = $this->getKepubifyEnabler()->disable();

        try {
            $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
            self::assertNotNull($book, 'The book is not linked to the Kobo');

            /** @var DownloadHelper $downloadHelper */
            $downloadHelper = self::getContainer()->get(DownloadHelper::class);

            self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');


            $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.%s', $this->accessKey, BookFixture::ID, MetadataResponseService::KEPUB_FORMAT));
            self::assertResponseStatusCodeSame(404); // We can not download kepub as the conversion is disabled
        } finally {
            // Re-enable Kepubify conversion for other tests
            $this->getKepubifyEnabler()->setKepubifyBinary($lastValue);
        }
    }

    public function testDownloadKepub(): void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();


        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

        $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.'.MetadataResponseService::KEPUB_FORMAT, $this->accessKey, BookFixture::ID));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/epub+zip');
        self::assertResponseHasHeader('Content-Length');

        $expectedDisposition = "attachment; filename=book-1-TheOdysses.kepub; filename*=utf-8''TheOdysses.kepub";
        self::assertResponseHeaderSame('Content-Disposition', $expectedDisposition, 'The Content-Disposition header is not as expected');

    }

    private function findByIdAndKobo(int $bookId, KoboDevice $kobo): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKoboDevice($bookId, $kobo);
    }

    private function getKepubifyEnabler(): KepubifyEnabler
    {
        $service = self::getContainer()->get(KepubifyEnabler::class);
        assert($service instanceof KepubifyEnabler);

        return $service;
    }
}