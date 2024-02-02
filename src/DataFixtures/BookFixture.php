<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookFixture extends Fixture implements DependentFixtureInterface
{
    public const BOOK_REFERENCE = 'book-hobbit';
    public const ID = 1;

    public function load(ObjectManager $manager): void
    {
        $book = new Book();
        $book->setTitle('The Hobbit');
        $book->setAuthors(['J.R.R. Tolkien']);
        $book->setPublishDate(new \DateTimeImmutable('1937-09-21'));
        $book->setLanguage('en');
        $book->setPublisher('Allen & Unwin');
        $book->setExtension('epub');
        $book->setImageExtension('jpg');
        $book->setImageFilename('the-hobbit');
        $book->setBookFilename('the-hobbit');
        $book->setChecksum(md5($book->getBookFilename()));
        $book->setBookPath('tests/');

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
