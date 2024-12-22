<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\Kepubify\KepubifyEnabler;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractKoboControllerTest extends WebTestCase
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

    public function getKoboDevice(): KoboDevice
    {
        $repository = $this->getEntityManager()->getRepository(KoboDevice::class);
        $kobo = $repository->findOneBy(['id' => 1]);
        if ($kobo === null) {
            throw new \RuntimeException('Unable to find a Kobo, please load fixtures');
        }

        return $kobo;
    }

    protected function getBook(): Book
    {
        // @phpstan-ignore-next-line
        return $this->getEntityManager()->getRepository(Book::class)->find(BookFixture::ID);
    }

    protected static function getJsonResponse(): array
    {
        if (!self::getClient() instanceof AbstractBrowser) {
            static::fail('A client must be initialized to make assertions');
        }

        /** @var Response $response */
        $response = self::getClient()->getResponse();
        self::assertResponseIsSuccessful();
        $content = $response->getContent();

        if ($content === false) {
            static::fail('Unable to read response content');
        }

        try {
            return (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid JSON', 0, $exception);
        }
    }

    protected function getKepubifyEnabler(): KepubifyEnabler
    {
        $service = self::getContainer()->get(KepubifyEnabler::class);
        assert($service instanceof KepubifyEnabler);

        return $service;
    }

    protected function getKoboStoreProxy(): KoboStoreProxy
    {
        $service = self::getContainer()->get(KoboStoreProxy::class);
        assert($service instanceof KoboStoreProxy);

        return $service;
    }

    protected function getKoboProxyConfiguration(): KoboProxyConfiguration
    {
        $service = self::getContainer()->get(KoboProxyConfiguration::class);
        assert($service instanceof KoboProxyConfiguration);

        return $service;
    }

    /**
     * @template T of object
     * @param class-string<T> $name
     * @return T
     */
    protected function getService(string $name): mixed
    {
        $service = self::getContainer()->get($name);
        if (!$service instanceof $name) {
            throw new \RuntimeException(sprintf('Service %s not found', $name));
        }
        assert($service instanceof $name);

        return $service;
    }

    protected function getMockClient(string $returnValue, int $code = 200): ClientInterface
    {
        $mock = new MockHandler([
            new \GuzzleHttp\Psr7\Response($code, ['Content-Type' => 'application/json'], $returnValue),
        ]);

        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    protected function enableRemoteSync(): void
    {
        // Enable remote sync
        $this->getKoboDevice()->setUpstreamSync(true);
        $this->getKoboProxyConfiguration()->setEnabled(true);
        $this->getEntityManager()->flush();
    }
}
