<?php

namespace App\Tests\Controller;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\Kobo;
use App\Kobo\DownloadHelper;

class KoboDownloadControllerTest extends AbstractKoboControllerTest
{
    public function testDownload(): void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();


        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKobo());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->exists($book), 'The book file does not exist');

        $client?->request('GET', sprintf('/kobo/%s/v1/download/%s.epub', $this->accessKey, BookFixture::ID));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/epub+zip');
        self::assertResponseHasHeader('Content-Length');

        $expectedDisposition = "attachment; filename=\"book-1-TheOdysses.epub\"; filename*=UTF-8''TheOdysses.epub";
        self::assertResponseHeaderSame('Content-Disposition', $expectedDisposition, 'The Content-Disposition header is not as expected');

    }

    private function findByIdAndKobo(int $bookId, Kobo $kobo): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKobo($bookId, $kobo);
    }
}