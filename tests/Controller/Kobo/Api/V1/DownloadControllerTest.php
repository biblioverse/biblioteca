<?php

namespace App\Tests\Controller\Kobo\Api\V1;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\Response\MetadataResponseService;
use App\Repository\BookRepository;
use App\Tests\Controller\Kobo\KoboControllerTestCase;

class DownloadControllerTest extends KoboControllerTestCase
{
    public function testDownload(): void
    {
        $client = static::getClient();

        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

        $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.%s', KoboFixture::ACCESS_KEY, BookFixture::ID, 'epub'));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/epub+zip');
        self::assertResponseHasHeader('Content-Length');

        $expectedDisposition = 'attachment; filename=book-1-'.BookFixture::BOOK_ODYSSEY_FILENAME."; filename*=utf-8''".BookFixture::BOOK_ODYSSEY_FILENAME;
        self::assertResponseHeaderSame('Content-Disposition', $expectedDisposition, 'The Content-Disposition header is not as expected');
    }

    public function testDownloadMissingBook(): void
    {
        $book = $this->getService(BookRepository::class)
           ->findByUuid(BookFixture::UUID_JUNGLE_BOOK);
        self::assertNotNull($book, 'Unable to load book');

        $client = self::getClient();

        $client?->request(
            'GET',
            sprintf('/kobo/%s/v1/download/%s.%s', KoboFixture::ACCESS_KEY, $book->getId(), MetadataResponseService::KEPUB_FORMAT)
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testDownloadKepubFailed(): void
    {
        $client = static::getClient();

        // Disable Kepubify conversion
        $lastValue = $this->getKepubifyEnabler()->disable();

        try {
            $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
            self::assertNotNull($book, 'The book is not linked to the Kobo');

            /** @var DownloadHelper $downloadHelper */
            $downloadHelper = self::getContainer()->get(DownloadHelper::class);

            self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

            $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.%s', KoboFixture::ACCESS_KEY, BookFixture::ID, MetadataResponseService::KEPUB_FORMAT));
            self::assertResponseStatusCodeSame(404); // We can not download kepub as the conversion is disabled
        } finally {
            // Re-enable Kepubify conversion for other tests
            $this->getKepubifyEnabler()->setKepubifyBinary($lastValue);
        }
    }

    public function testDownloadKepub(): void
    {
        $client = static::getClient();

        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

        $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.'.MetadataResponseService::KEPUB_FORMAT, KoboFixture::ACCESS_KEY, BookFixture::ID));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/epub+zip');
        self::assertResponseHasHeader('Content-Length');

        $expectedDisposition = 'attachment; filename=book-1-'.BookFixture::BOOK_ODYSSEY_FILENAME."; filename*=utf-8''".BookFixture::BOOK_ODYSSEY_FILENAME;
        $expectedDisposition = str_replace('.epub', '.kepub', $expectedDisposition);
        self::assertResponseHeaderSame('Content-Disposition', $expectedDisposition, 'The Content-Disposition header is not as expected');
    }

    private function findByIdAndKobo(int $bookId, KoboDevice $koboDevice): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKoboDevice($bookId, $koboDevice);
    }
}
