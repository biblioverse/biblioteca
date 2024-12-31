<?php

namespace App\Tests\Controller\Opds;

use App\DataFixtures\OpdsAccessFixture;
use App\DataFixtures\UserFixture;
use App\Entity\OpdsAccess;
use App\Repository\OpdsAccessRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractOpdsTestController extends WebTestCase
{
    protected function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        self::createClient();
    }

    protected static function getXmlResponse(): array
    {
        if (!self::getClient() instanceof AbstractBrowser) {
            static::fail('A client must be initialized to make assertions');
        }

        /** @var Response $response */
        $response = self::getClient()->getResponse();
        $content = $response->getContent();

        if ($content === false) {
            static::fail('Unable to read response content');
        }
        $getXML = simplexml_load_string($content);
        if ($getXML === false) {
            static::fail('Unable to parse response XML');
        }
        $encoded = json_encode($getXML);

        if ($encoded === false) {
            static::fail('Unable to encode response XML');
        }

        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            static::fail('Unable to decode response content');
        }

        return $decoded;
    }

    protected function ensureFixtureExists(): void
    {
        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        /* @phpstan-ignore-next-line */
        $entityManager = $doctrine->getManager();

        $opdsRepo = static::getContainer()->get(OpdsAccessRepository::class);

        if (!$opdsRepo instanceof OpdsAccessRepository) {
            self::fail('OpdsAccessRepository not found');
        }

        $exist = $opdsRepo->findOneBy(['token' => OpdsAccessFixture::ACCESS_KEY]);

        if ($exist === null) {
            $userRepository = static::getContainer()->get(UserRepository::class);

            if (!$userRepository instanceof UserRepository) {
                self::fail('UserRepository not found');
            }

            $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

            if (!$testUser instanceof UserInterface) {
                self::fail('User not found');
            }

            $all = $opdsRepo->findAll();
            foreach ($all as $opds) {
                $entityManager->remove($opds);
            }
            $entityManager->flush();

            $opdsAccess = new OpdsAccess();
            $opdsAccess->setToken(OpdsAccessFixture::ACCESS_KEY);
            $opdsAccess->setUser($testUser);
            $entityManager->persist($opdsAccess);
            $entityManager->flush();
        }
    }
}
