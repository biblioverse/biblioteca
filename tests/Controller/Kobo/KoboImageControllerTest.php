<?php

namespace App\Tests\Controller\Kobo;

use App\Controller\Kobo\Api\ImageController;
use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ImageController::class)]
class KoboImageControllerTest extends KoboControllerTestCase
{
    public static function urlProvider(): array
    {
        return [
            [
                'url' => sprintf('/kobo/%s/%s/300/200/80/isGreyscale/image.jpg', KoboFixture::ACCESS_KEY, BookFixture::UUID),
                'contentType' => 'image/jpeg',
            ],
            [
                'url' => sprintf('/kobo/%s/%s/300/200/80/isGreyscale/image.png', KoboFixture::ACCESS_KEY, BookFixture::UUID),
                'contentType' => 'image/png',
            ],            [
                'url' => sprintf('/kobo/%s/%s/300/200/80/isGreyscale/image.gif', KoboFixture::ACCESS_KEY, BookFixture::UUID),
                'contentType' => 'image/gif',
            ],
        ];
    }

    #[DataProvider('urlProvider')]
    public function testDownload(string $url, string $contentType): void
    {
        $client = static::getClient();

        $book = $this->findByIdAndKobo(BookFixture::ID, $this->getKoboDevice());
        self::assertNotNull($book, 'The book is not linked to the Kobo');

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = self::getContainer()->get(DownloadHelper::class);

        self::assertTrue($downloadHelper->coverExist($book), 'The book cover does not exist');

        $client?->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', $contentType);
    }

    private function findByIdAndKobo(int $bookId, KoboDevice $koboDevice): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findByIdAndKoboDevice($bookId, $koboDevice);
    }
}
