<?php

namespace App\DataFixtures;

use App\Entity\KoboDevice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class KoboFixture extends Fixture implements DependentFixtureInterface
{
    public const KOBO_REFERENCE = 'kobo';

    public function load(ObjectManager $manager): void
    {
        $kobo = new KoboDevice();
        $kobo->setAccessKey('0000-0000-0000-0000');
        $kobo->setName('test kobo');
        $kobo->setUser($this->getUser());

        $manager->persist($kobo);
        $manager->flush();
        $this->addReference(self::KOBO_REFERENCE, $kobo);
    }

    protected function getUser(): User
    {
        // @phpstan-ignore-next-line
        return $this->getReference(UserFixture::USER_REFERENCE);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
