<?php

namespace App\Tests\Controller;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\Kobo;
use App\Kobo\DownloadHelper;

class KoboImageControllerTest extends AbstractKoboControllerTest
{
    public function testDownload(): void
    {
        $client = static::getClient();
        $this->injectFakeFileSystemManager();


        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKobo());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->coverExist($book), 'The book cover does not exist');

        $client?->request('GET', sprintf('/kobo/%s/%s/300/200/80/isGreyscale/image.jpg', $this->accessKey, $book->getUuid()));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/jpeg');
    }

    private function findByIdAndKobo(int $bookId, Kobo $kobo): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKobo($bookId, $kobo);
    }
}