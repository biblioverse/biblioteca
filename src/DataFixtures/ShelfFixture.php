<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Shelf;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShelfFixture extends Fixture implements DependentFixtureInterface
{
    public const SHELF_REFERENCE = 'shelf';
    public const SHELF_NAME = 'test shelf';

    public function load(ObjectManager $manager): void
    {
        $shelf = new Shelf();
        $shelf->setName(self::SHELF_NAME);
        $shelf->setUser($this->getUser());
        $this->getBook()->addShelf($shelf);

        foreach (range(0, BookFixture::NUMBER_OF_OWNED_YAML_BOOKS - 1) as $index) {
            $book = $this->getReference('book-'.$index, Book::class);
            $book->addShelf($shelf);
        }

        $manager->persist($shelf);
        $manager->flush();

        $this->addReference(self::SHELF_REFERENCE, $shelf);
    }

    protected function getUser(): User
    {
        return $this->getReference(UserFixture::USER_REFERENCE, User::class);
    }

    protected function getBook(): Book
    {
        return $this->getReference(BookFixture::BOOK_REFERENCE, Book::class);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            BookFixture::class,
            KoboFixture::class,
        ];
    }
}
