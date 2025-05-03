<?php

namespace App\Tests;

use App\DataFixtures\BookFixture;
use App\DataFixtures\ShelfFixture;
use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Entity\Shelf;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait TestCaseHelperTrait
{
    protected function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager;
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

    protected function getBook(array $criteria = []): Book
    {
        if ($criteria === []) {
            $criteria = ['id' => BookFixture::ID];
        }

        // @phpstan-ignore-next-line
        return $this->getEntityManager()->getRepository(Book::class)->findOneBy($criteria);
    }

    protected function getShelf(array $criteria = []): Shelf
    {
        if ($criteria === []) {
            $criteria = ['name' => ShelfFixture::SHELF_NAME];
        }

        // @phpstan-ignore-next-line
        return $this->getEntityManager()->getRepository(Shelf::class)->findOneBy($criteria);
    }

    /**
     * @template T of object
     * @param class-string<T> $name
     * @return T
     */
    protected function getService(string $name): mixed
    {
        $service = self::getContainer()->get($name);
        self::assertInstanceOf($name, $service);

        return $service;
    }

    /**
     * @param array<string,string> $headers
     */
    protected function getMockClient(string $returnValue, int $code = 200, array $headers = []): ClientInterface
    {
        $mock = new MockHandler([
            new Response($code, $headers + ['Content-Type' => 'application/json'], $returnValue),
        ]);

        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    protected function markAllBooksAsSynced(\DateTimeImmutable $when): void
    {
        $em = $this->getEntityManager();
        $books = $this->getService(BookRepository::class)->findAll();

        $koboDevice = $this->getKoboDevice();
        foreach ($books as $book) {
            $syncedBook = new KoboSyncedBook($when, $when, $koboDevice, $book);
            $book->addKoboSyncedBook($syncedBook);
            $em->persist($syncedBook);
        }
        $em->flush();
    }

    protected function changeAllBooksDate(\DateTimeImmutable $when): void
    {
        // It seems that Gedmo take over, so we tells him the date too
        (new TestClock())->setTime($when);
        $this->getEntityManager()->createQueryBuilder()
            ->update(Book::class, 'b')
            ->set('b.updated', ':when')
            ->set('b.created', ':when')
            ->where('b.id > 0')
            ->setParameter('when', $when)
            ->getQuery()
            ->execute();
    }

    protected function changeAllShelvesDate(\DateTimeImmutable $when): void
    {
        // It seems that Gedmo take over, so we tells him the date too
        (new TestClock())->setTime($when);
        $this->getEntityManager()->createQueryBuilder()
            ->update(Shelf::class, 's')
            ->set('s.updated', ':when')
            ->set('s.created', ':when')
            ->orWhere('s.id > 0')
            ->setParameter('when', $when)
            ->getQuery()
            ->execute();
    }

    protected function deleteAllSyncedBooks(): void
    {
        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks($this->getKoboDevice());
    }

    protected function deleteAllInteractions(): void
    {
        $this->getEntityManager()->getRepository(BookInteraction::class)->createQueryBuilder('i')->delete()->getQuery()->execute();
    }

    protected function loginViaTokenStorage(): void
    {
        $token = new UsernamePasswordToken($this->getKoboDevice()->getUser(), 'main', $this->getKoboDevice()->getUser()->getRoles());
        $this->getService(TokenStorageInterface::class)->setToken($token);
    }
}
