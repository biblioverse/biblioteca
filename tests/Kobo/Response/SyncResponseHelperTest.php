<?php

namespace App\Tests\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Kobo\Response\SyncResponseHelper;
use App\Kobo\SyncToken\SyncTokenV1;
use App\Tests\TestClock;
use PHPUnit\Framework\TestCase;

class SyncResponseHelperTest extends TestCase
{
    public function testIsNewEntitlement(): void
    {
        $clock = new TestClock();
        $book = $this->createBook();

        $clock->alter('+1 second');
        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        self::assertTrue($syncResponseHelper->isNewEntitlement($book));

        $clock->alter('- 1 day');
        $book = $this->createBook();
        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));
        self::assertFalse($syncResponseHelper->isNewEntitlement($book));
    }

    public function testIsChangedEntitlementFalseWhenLastModifiedEmpty(): void
    {
        $clock = new TestClock();
        $book = $this->createBook();
        $clock->alter('+1 day');

        $syncToken = $this->createSyncToken();
        $syncToken->lastModified = null;

        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));

        self::assertFalse($syncResponseHelper->isChangedEntitlement($book), 'entitlement must be changed');
    }

    public function testIsChangedEntitlementBasedOnUpdatedDate(): void
    {
        $clock = new TestClock();
        $book = $this->createBook();
        $koboDevice = $this->createKoboDevice();
        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));

        $clock->alter('+1 day');
        $syncToken = $this->createSyncToken();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        // Book younger than the token
        $book->setUpdated((new TestClock())->now()->modify('-1 day'));
        self::assertFalse($syncResponseHelper->isChangedEntitlement($book));

        // Book older than the token
        $book->setUpdated((new TestClock())->now()->modify('+1 day'));
        self::assertTrue($syncResponseHelper->isChangedEntitlement($book));
    }

    public function testIsArchivedEntitlement(): void
    {
        $clock = new TestClock();
        $book = $this->createBook();
        $clock->alter('+1 day');

        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        $syncedBook = $this->createKoboSyncedBook($koboDevice, $book);
        $syncedBook->setArchived($clock->now()->modify('-1 day'));
        $book->addKoboSyncedBook($syncedBook);

        // Book was marked as removed before the token
        self::assertTrue($syncResponseHelper->isChangedEntitlement($book), 'entitlement must be new');
        self::assertTrue($syncResponseHelper->isArchivedEntitlement($book), 'entitlement must be archived');
    }

    private function createKoboDevice(): KoboDevice
    {
        return new KoboDevice();
    }

    private function createBook(): Book
    {
        $clock = new TestClock();
        $book = new Book();
        $book->setCreated($clock->now());
        $book->setUpdated($clock->now());

        return $book;
    }

    private function createSyncToken(): SyncTokenV1
    {
        $clock = new TestClock();
        $syncToken = new SyncTokenV1();
        $syncToken->lastCreated = $clock->now();
        $syncToken->lastModified = $clock->now();
        $syncToken->tagLastModified = $clock->now();
        $syncToken->readingStateLastModified = $clock->now();

        return $syncToken;
    }

    private function createKoboSyncedBook(KoboDevice $koboDevice, Book $book): KoboSyncedBook
    {
        $syncedBook = new KoboSyncedBook(new \DateTimeImmutable('now'), null, $koboDevice, $book);
        $book->addKoboSyncedBook($syncedBook);

        return $syncedBook;
    }

    public function testIsArchivedEntitlement2(): void
    {
        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $book = $this->createBook();
        $syncedBook = $this->createKoboSyncedBook($koboDevice, $book);

        $syncToken->archiveLastModified = new \DateTimeImmutable('2025-01-31 19:00:00');
        $syncedBook->setArchived(new \DateTimeImmutable('2025-01-31 20:00:00'));

        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        self::assertTrue($syncResponseHelper->isArchivedEntitlement($book), 'entitlement must be archived');
        self::assertTrue($syncResponseHelper->isChangedEntitlement($book), 'entitlement must be changed');
    }
}
