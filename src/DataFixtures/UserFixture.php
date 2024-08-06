<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public const USER_REFERENCE = 'user';
    public const USER_USERNAME = 'admin@example.com';
    public const USER_PASSWORD = 'admin@example.com';

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername(self::USER_USERNAME);
        $user->setBirthday(new \DateTimeImmutable('1990-01-01'));
        $user->setPassword($this->passwordHasher->hashPassword($user, self::USER_PASSWORD));
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::USER_REFERENCE, $user);
    }
}
