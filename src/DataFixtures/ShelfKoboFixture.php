<?php

namespace App\DataFixtures;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
class ShelfKoboFixture extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $kobo = $this->getReference(KoboFixture::KOBO_REFERENCE, KoboDevice::class);

        $kobo->addShelf($this->getReference(ShelfFixture::SHELF_REFERENCE, Shelf::class));

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ShelfFixture::class,
            KoboFixture::class,
        ];
    }
}
