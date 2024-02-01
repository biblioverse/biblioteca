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

    public function load(ObjectManager $manager): void
    {
        $shelf = new Shelf();
        $shelf->setName('test shelf');
        $shelf->setUser($this->getUser());
        $this->getBook()->addShelf($shelf);

        $manager->persist($shelf);
        $manager->flush();

        $this->addReference(self::SHELF_REFERENCE, $shelf);
    }

    protected function getUser(): User
    {
        // @phpstan-ignore-next-line
        return $this->getReference(UserFixture::USER_REFERENCE);
    }

    protected function getBook(): Book
    {
        // @phpstan-ignore-next-line
        return $this->getReference(BookFixture::BOOK_REFERENCE);
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
