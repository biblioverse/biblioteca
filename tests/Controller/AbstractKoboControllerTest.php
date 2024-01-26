<?php

namespace App\Tests\Controller;

use App\Entity\Kobo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractKoboControllerTest extends WebTestCase
{

    protected ?string $accessKey = null;


    protected function setUp(): void
    {
        self::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => 'test@example.com']);
        if($user === null) {
            $user = new User();
            $user->setUsername('test@example.com');
            $user->setPassword('test@example.com');
            $entityManager->persist($user);
        }

        $kobo = $user->getKobos()->current();
        if($kobo == null) {
            $kobo = new Kobo();
            $kobo->setUser($user);
            $entityManager->persist($kobo);
            $entityManager->flush();
        }

        $this->accessKey = $kobo->getAccessKey();
    }

    protected static function getJsonResponse(): array
    {
        if (null === self::getClient()) {
            static::fail('A client must be initialized to make assertions');
        }

        /** @var Response $response */
        $response = self::getClient()->getResponse();
        $content = $response->getContent();

        if($content === false) {
            static::fail('Unable to read response content');
        }

        return (array)json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

}