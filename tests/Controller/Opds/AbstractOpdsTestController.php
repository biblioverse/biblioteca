<?php

namespace App\Tests\Controller\Opds;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

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
}
