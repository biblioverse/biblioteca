<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public const USER_REFERENCE = 'user';

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('admin@example.com');
        $user->setBirthday(new \DateTimeImmutable('1990-01-01'));
        $user->setPassword('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::USER_REFERENCE, $user);
    }
}
