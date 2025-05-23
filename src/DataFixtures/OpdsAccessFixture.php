<?php

namespace App\DataFixtures;

use App\Entity\OpdsAccess;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
class OpdsAccessFixture extends Fixture implements DependentFixtureInterface
{
    public const ACCESS_KEY = 'test-access-key';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $opdsAccess = new OpdsAccess($this->getUser());
        $opdsAccess->setToken(self::ACCESS_KEY);

        $manager->persist($opdsAccess);
        $manager->flush();
    }

    protected function getUser(): User
    {
        return $this->getReference(UserFixture::USER_REFERENCE, User::class);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
