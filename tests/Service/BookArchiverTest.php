<?php

namespace App\Tests\Service;

use App\DataFixtures\BookFixture;
use App\Entity\BookInteraction;
use App\Entity\Shelf;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use App\Service\BookArchiver;
use App\Tests\TestCaseHelperTrait;
use App\Tests\TestClock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookArchiverTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteAllSyncedBooks();
        $this->getEntityManager()->getRepository(BookInteraction::class)->createQueryBuilder('bi')->delete()->getQuery()->execute();
        $this->changeAllBooksDate(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $this->changeAllShelvesDate(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $this->getEntityManager()->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Delete the created shelf
        $this->getService(ShelfRepository::class)->deleteByName('test-archived');

        // Put back MobyDick in the shelf
        $mobyDick = $this->getBook(['uuid' => BookFixture::UUID_MOBY_DICK]);
        $this->getShelf()->addBook($mobyDick);

        $this->getEntityManager()->flush();

        parent::tearDown();
    }

    public function testArchiveBookFromShelf(): void
    {
        $this->markAllBooksAsSynced(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $mobyDick = $this->getBook(['uuid' => BookFixture::UUID_MOBY_DICK]);

        // Make sure the book is not in a shelf anymore.
        $this->getShelf()->removeBook($mobyDick);

        $this->getEntityManager()->flush();
        $shelf = $this->getShelf();

        $this->loginViaTokenStorage();
        $bookArchiver = $this->getService(BookArchiver::class);
        $bookArchiver->archiveBookFromShelf($shelf, $mobyDick);

        $syncedBook = $this->getService(KoboSyncedBookRepository::class)->findOneBy([
            'book' => $mobyDick,
            'koboDevice' => $this->getKoboDevice(),
        ]);

        self::assertNotNull($syncedBook, 'book should be synced');
        self::assertTrue($syncedBook->isArchived(), 'book should be archived');
    }

    public function testUnArchiveBookFromShelf(): void
    {
        $this->markAllBooksAsSynced(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $mobyDick = $this->getBook(['uuid' => BookFixture::UUID_MOBY_DICK]);
        $syncedBook = $this->getService(KoboSyncedBookRepository::class)->findOneBy([
            'book' => $mobyDick,
            'koboDevice' => $this->getKoboDevice(),
        ]);
        $syncedBook?->setArchived(new \DateTimeImmutable('now'));

        $this->getEntityManager()->flush();
        $shelf = $this->getShelf();

        $this->loginViaTokenStorage();
        $bookArchiver = $this->getService(BookArchiver::class);
        $bookArchiver->unArchiveBookFromShelf($shelf, $mobyDick);

        $syncedBook = $this->getService(KoboSyncedBookRepository::class)->findOneBy([
            'book' => $mobyDick,
            'koboDevice' => $this->getKoboDevice(),
        ]);

        self::assertNotNull($syncedBook, 'book should be synced');
        self::assertFalse($syncedBook->isArchived(), 'book should be un-archived');
    }

    public function testArchiveBookFromShelfBypass(): void
    {
        $clock = new TestClock();
        $this->markAllBooksAsSynced(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $mobyDick = $this->getBook(['uuid' => BookFixture::UUID_MOBY_DICK]);

        // Add a second shelf to be synced with the kobo
        $shelf = new Shelf();
        $shelf->setCreated($clock->now());
        $shelf->setUpdated($clock->now());
        $shelf->setUser($this->getKoboDevice()->getUser());
        $shelf->setName('test-archived');
        $this->getKoboDevice()->addShelf($shelf);
        $shelf->addBook($mobyDick);
        $this->getEntityManager()->flush();

        $shelf = $this->getShelf();
        $this->loginViaTokenStorage();
        $bookArchiver = $this->getService(BookArchiver::class);
        $bookArchiver->archiveBookFromShelf($shelf, $mobyDick);

        $syncedBook = $this->getService(KoboSyncedBookRepository::class)->findOneBy([
            'book' => $mobyDick,
            'koboDevice' => $this->getKoboDevice(),
        ]);

        self::assertNotNull($syncedBook, 'book should be synced');
        self::assertFalse($syncedBook->isArchived(), 'book should not be archived as used in 2 shelves');
    }
}
