<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;

class KoboImageControllerTest extends AbstractKoboControllerTest
{
    public function testDownload(): void
    {
        $client = static::getClient();

        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->coverExist($book), 'The book cover does not exist');

        $client?->request('GET', sprintf('/kobo/%s/%s/300/200/80/isGreyscale/image.jpg', KoboFixture::ACCESS_KEY, $book->getUuid()));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/jpeg');
    }

    private function findByIdAndKobo(int $bookId, KoboDevice $koboDevice): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKoboDevice($bookId, $koboDevice);
    }
}
