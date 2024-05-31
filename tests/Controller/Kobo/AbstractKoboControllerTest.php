<?php

namespace App\Tests\Controller\Kobo;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Service\BookFileSystemManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

abstract class AbstractKoboControllerTest extends WebTestCase
{

    const DEFAULT_BOOK_FOLDER_NAMING_FORMAT = '{authorFirst}/{author}/{title}/{serie}';
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
        if($refresh && $this->koboDevice !== null){
            $this->getEntityManager()->refresh($this->koboDevice);
        }
        if($this->koboDevice === null) {
            throw new \RuntimeException('Kobo not initialized');
        }
        return $this->koboDevice;
    }

    private function getBook(): Book
    {
        // @phpstan-ignore-next-line
        return $this->getEntityManager()->getRepository(Book::class)->find(BookFixture::ID);
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

    protected function injectFakeFileSystemManager(): void
    {
        $resources = __DIR__.'/../../Resources';
        $fixtureBookPath = realpath($resources)."/";
        $mockBuilder = $this->getMockBuilder(BookFileSystemManager::class);

        $mock =  $mockBuilder->setConstructorArgs([
            self::getContainer()->get(Security::class),
            realpath($resources),
            self::DEFAULT_BOOK_FOLDER_NAMING_FORMAT,
            $this->createMock(SluggerInterface::class),
            new NullLogger(),
        ])
            ->onlyMethods(['getBooksDirectory', 'getCoverDirectory'])
            ->enableProxyingToOriginalMethods()
            ->getMock();
        $mock->expects(self::any())->method('getBooksDirectory')->willReturn($fixtureBookPath);
        $mock->expects(self::any())->method('getCoverDirectory')->willReturn($fixtureBookPath);

        self::assertSame(realpath($resources).'/books/TheOdysses.epub', $mock->getBookFilename($this->getBook()), "Faking Filesystem failed");
        self::getContainer()->set(BookFileSystemManager::class, $mock);
    }

}