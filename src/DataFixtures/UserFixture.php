<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @codeCoverageIgnore
 */
class UserFixture extends Fixture
{
    public const USER_REFERENCE = 'user';
    public const CHILD_USER_REFERENCE = 'user.child';
    public const USER_USERNAME = 'admin@example.com';
    public const USER_PASSWORD = 'admin@example.com';

    public const CHILD_USERNAME = 'child@example.com';
    public const CHILD_PASSWORD = 'child@example.com';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername(self::USER_USERNAME);
        $user->setBirthday(new \DateTimeImmutable('1990-01-01'));
        $user->setLanguage('en');
        $user->setPassword($this->passwordHasher->hashPassword($user, self::USER_PASSWORD));
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::USER_REFERENCE, $user);

        $user = new User();
        $user->setUsername(self::CHILD_USERNAME);
        $user->setBirthday(new \DateTimeImmutable('1990-01-01'));
        $user->setLanguage('en');
        $user->setMaxAgeCategory(1);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::CHILD_PASSWORD));
        $user->setRoles(['ROLE_USER']);

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::CHILD_USER_REFERENCE, $user);
    }
}
