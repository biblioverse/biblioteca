<?php

namespace App\DataFixtures;

use App\Entity\KoboDevice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
class KoboFixture extends Fixture implements DependentFixtureInterface
{
    public const KOBO_REFERENCE = 'kobo';
    public const ACCESS_KEY = '0000-0000-0000-0000';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $kobo = new KoboDevice();
        $kobo->setAccessKey(self::ACCESS_KEY);
        $kobo->setName('test kobo');
        $kobo->setUser($this->getUser(UserFixture::CHILD_USER_REFERENCE));

        $manager->persist($kobo);
        $manager->flush();
        $this->addReference(self::KOBO_REFERENCE, $kobo);
    }

    protected function getUser(string $reference = UserFixture::USER_REFERENCE): User
    {
        return $this->getReference($reference, User::class);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
