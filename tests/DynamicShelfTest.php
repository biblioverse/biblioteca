<?php

use App\Entity\Shelf;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class DynamicShelfTest extends WebTestCase
{
    public function testDynamicShelf(): void
    {
        $client = static::createClient();
        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => 'admin@example.com']);

        self::assertInstanceOf(UserInterface::class, $testUser);

        $client->loginUser($testUser);
        $shelf = new Shelf();
        $shelf->setName('test-dyn');
        $shelf->setQueryString('*');
        $shelf->setUser($testUser);
        $entityManager->persist($shelf);
        $entityManager->flush();

        $client->request(Request::METHOD_GET, '/shelf/test-dyn');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.book');
    }
}
