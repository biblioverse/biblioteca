<?php

namespace App\Tests\Controller\Kobo;

use App\Kobo\Kepubify\KepubifyEnabler;
use App\Tests\InjectFakeFileSystemTrait;
use Symfony\Component\BrowserKit\AbstractBrowser;
use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractKoboControllerTest extends WebTestCase
{
    use InjectFakeFileSystemTrait;

    protected ?string $accessKey = null;
    protected ?KoboDevice $koboDevice = null;

    protected function getEntityManager(): EntityManagerInterface{
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        return $entityManager;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::createClient();

        $this->koboDevice = $this->loadKoboDevice();
        $this->accessKey = $this->koboDevice->getAccessKey();
    }

    public function getKoboDevice(bool $refresh = false): KoboDevice
    {
        if($refresh && $this->koboDevice instanceof KoboDevice){
            $this->getEntityManager()->refresh($this->koboDevice);
        }
        if(!$this->koboDevice instanceof KoboDevice) {
            throw new \RuntimeException('Kobo not initialized');
        }
        return $this->koboDevice;
    }

    protected function getBook(): Book
    {
        // @phpstan-ignore-next-line
        return $this->getEntityManager()->getRepository(Book::class)->find(BookFixture::ID);
    }

    /**
     * @throws \JsonException
     */
    protected static function getJsonResponse(): array
    {
        if (!self::getClient() instanceof AbstractBrowser) {
            static::fail('A client must be initialized to make assertions');
        }

        /** @var Response $response */
        $response = self::getClient()->getResponse();
        self::assertResponseIsSuccessful();
        $content = $response->getContent();

        if($content === false) {
            static::fail('Unable to read response content');
        }

        return (array)json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function loadKoboDevice(): KoboDevice
    {
        $repository = $this->getEntityManager()->getRepository(KoboDevice::class);
        $kobo = $repository->findOneBy(['id' => 1]);
        if($kobo === null) {
            throw new \RuntimeException('Unable to find a Kobo, please load fixtures');
        }

        return $kobo;
    }

    protected function getKepubifyEnabler(): KepubifyEnabler
    {
        $service = self::getContainer()->get(KepubifyEnabler::class);
        assert($service instanceof KepubifyEnabler);

        return $service;
    }

}