<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShelfKoboFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $kobo = $this->getReference(KoboFixture::KOBO_REFERENCE);
        // @phpstan-ignore-next-line
        $kobo->addShelf($this->getReference(ShelfFixture::SHELF_REFERENCE));

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ShelfFixture::class,
            KoboFixture::class,
        ];
    }
}
