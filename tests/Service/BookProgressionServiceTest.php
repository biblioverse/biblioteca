<?php

namespace App\Tests\Service;

use App\DataFixtures\BookFixture;
use App\DataFixtures\UserFixture;
use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Service\BookProgressionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookProgressionServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testPageNumber(): void
    {
        $bookProgression = $this->getProgressionService();
        $book = $this->getBook();

        $fakePageNumber = 10;
        $book->setPageNumber($fakePageNumber);

        // Read from existing entity
        self::assertSame($fakePageNumber, $bookProgression->processPageNumber($book), 'Book number should be read from entity');

        // Read from file if forced
        $expectedPageNumber = 503;
        self::assertSame($bookProgression->processPageNumber($book, true), $expectedPageNumber, 'Book number should not be 30');
        // Read from file must update the entity
        self::assertSame($book->getPageNumber(), $expectedPageNumber, 'Book number should be 30');
    }

    public function testUnknownProgress(): void
    {
        $service = $this->getProgressionService();
        $book = $this->getBook();

        // Make sure we have 0 interactions
        $book->getBookInteractions()->clear();
        $lastInteraction = $book->getLastInteraction($this->getUser());
        self::assertNull($lastInteraction, 'Interaction should not be created');

        $progression = $service->getProgression($this->getBook(), $this->getUser());
        self::assertNull($progression, 'Progression should be null when we do not know it');
    }

    public function testProgressMax(): void
    {
        $service = $this->getProgressionService();
        $book = $this->getBook();
        // Make sure we have 0 interactions
        $book->getBookInteractions()->clear();

        $service->setProgression($book, $this->getUser(), 1);
        $lastInteraction = $book->getLastInteraction($this->getUser());
        self::assertNotNull($lastInteraction, 'Interaction should be created');
        self::assertTrue($lastInteraction->isFinished(), 'Book should be finished');
        self::assertSame(30, $lastInteraction->getReadPages(), 'Book should have all page read');
    }

    public function testProgressReading(): void
    {
        $service = $this->getProgressionService();
        $book = $this->getBook();
        // Make sure we have 0 interactions
        $book->getBookInteractions()->clear();
        $service->setProgression($book, $this->getUser(), 0.5);
        $lastInteraction = $book->getLastInteraction($this->getUser());
        self::assertNotNull($lastInteraction, 'Interaction should be created');

        self::assertFalse($lastInteraction->isFinished(), 'Book should not be finished');
        self::assertSame(15, $lastInteraction->getReadPages(), 'Book should have half page read');
    }

    public function testMarkAsUnread(): void
    {
        $service = $this->getProgressionService();
        $book = $this->getBook();
        // Make sure we have 0 interactions
        $interaction = new BookInteraction();
        $interaction->setBook($book);
        $interaction->setUser($this->getUser());
        $interaction->setReadPages(12);

        $book->getBookInteractions()->add($interaction);
        $service->setProgression($book, $this->getUser(), null);
        $lastInteraction = $book->getLastInteraction($this->getUser());
        self::assertNotNull($lastInteraction, 'Interaction should be created');

        self::assertFalse($lastInteraction->isFinished(), 'Book should not be finished');
        self::assertNull($lastInteraction->getReadPages(), 'Book should have null page read');
    }

    private function getBook(): Book
    {
        /** @var BookRepository $repo */
        $repo = self::getContainer()->get(BookRepository::class);
        /** @var Book $book */
        $book = $repo->findOneBy([
            'id' => BookFixture::ID,
        ]);
        self::assertInstanceOf(Book::class, $book);

        return $book;
    }

    private function getProgressionService(): BookProgressionService
    {
        /** @var BookProgressionService $bookProgression */
        $bookProgression = self::getContainer()->get(BookProgressionService::class);

        return $bookProgression;
    }

    private function getUser(): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $user = $repo->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
