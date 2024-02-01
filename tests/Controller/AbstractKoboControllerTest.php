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

    protected function getEntityManager(): EntityManagerInterface{
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        return $entityManager;
    }

    protected function setUp(): void
    {
        self::createClient();

        $kobo = $this->getEntityManager()->getRepository(Kobo::class)->findOneBy(['id' => 1]);
        if($kobo === null) {
            throw new \RuntimeException('Unable to find a Kobo, please load fixtures');
        }

        $this->accessKey = $kobo->getAccessKey();
    }

    /**
     * @throws \JsonException
     */
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