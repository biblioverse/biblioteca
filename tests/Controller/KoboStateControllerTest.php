<?php

namespace App\Tests\Controller;

use App\DataFixtures\BookFixture;
use App\DataFixtures\ShelfFixture;
use App\Entity\Book;
use App\Entity\Shelf;
use Doctrine\ORM\EntityManager;

class KoboStateControllerTest  extends AbstractKoboControllerTest
{
    public function testOpen() : void
    {
        $client = static::getClient();
        $client?->setServerParameter('HTTP_CONNECTION', 'keep-alive');

        $book = $this->getBookById(BookFixture::ID);
        self::assertNotNull($book, 'Book '.BookFixture::ID.' not found');

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/'.$book->getUuid().'/state');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Connection', 'keep-alive');
    }

    protected function getBookById(int $id): ?Book
    {
        $book = $this->getEntityManager()->getRepository(Book::class)->findOneBy(['id' => $id]);

        /** @var Book|null */
        return $book;
    }


}