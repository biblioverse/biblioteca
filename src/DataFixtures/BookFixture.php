<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookFixture extends Fixture implements DependentFixtureInterface
{
    public const BOOK_REFERENCE = 'book-odyssey';
    public const ID = 1;
    public const UUID = '54c8fb05-cf05-4cb6-9482-bc25fa49fa80';

    public function load(ObjectManager $manager): void
    {
        // https://www.gutenberg.org/ebooks/1727
        $book = new Book();
        $book->setUuid(self::UUID);
        $book->setTitle('The Odyssey');
        $book->setAuthors(['Homer']);
        $book->setPublishDate(new \DateTimeImmutable('1999-04-01'));
        $book->setLanguage('en');
        $book->setPublisher('Public domain');
        $book->setExtension('epub');
        $book->setImageExtension('jpg');
        $book->setImageFilename('TheOdysses.jpg');
        $book->setImagePath('');
        $book->setBookFilename('TheOdysses.epub');
        $book->setChecksum(md5($book->getBookFilename()));
        $book->setBookPath('');
        $book->setPageNumber(30);

        $manager->persist($book);
        $manager->flush();

        $this->addReference(self::BOOK_REFERENCE, $book);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
