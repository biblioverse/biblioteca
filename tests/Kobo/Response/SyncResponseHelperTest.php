<?php

namespace App\Tests\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Kobo\Response\SyncResponseHelper;
use App\Kobo\SyncToken;
use App\Tests\TestClock;
use PHPUnit\Framework\TestCase;

class SyncResponseHelperTest extends TestCase
{
    public function testIsNewEntitlement(): void
    {
        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $book = $this->createBook();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        self::assertTrue($syncResponseHelper->isNewEntitlement($book));

        $book = $this->createBook();
        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));
        self::assertFalse($syncResponseHelper->isNewEntitlement($book));
    }

    public function testIsChangedEntitlementFalseWhenLastModifiedEmpty(): void
    {
        $syncToken = $this->createSyncToken();
        $syncToken->lastModified = null;

        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        $book = $this->createBook();
        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));

        self::assertFalse($syncResponseHelper->isChangedEntitlement($book));
    }

    public function testIsChangedEntitlementBasedOnUpdatedDate(): void
    {
        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        $book = $this->createBook();
        $book->setUpdated((new TestClock())->now());
        $book->addKoboSyncedBook($this->createKoboSyncedBook($koboDevice, $book));

        // Book younger than the token
        $book->setUpdated((new TestClock())->now()->modify('-1 second'));
        self::assertFalse($syncResponseHelper->isChangedEntitlement($book));

        // Book older than the token
        $book->setUpdated((new TestClock())->now()->modify('+1 second'));
        self::assertTrue($syncResponseHelper->isChangedEntitlement($book));
    }

    public function testIsArchivedEntitlement(): void
    {
        $syncToken = $this->createSyncToken();
        $koboDevice = $this->createKoboDevice();
        $syncResponseHelper = new SyncResponseHelper($syncToken, $koboDevice);

        $book = $this->createBook();
        $syncedBook = $this->createKoboSyncedBook($koboDevice, $book);
        $syncedBook->setArchived((new TestClock())->now()->modify('-1 second'));
        $book->setUpdated((new TestClock())->now());
        $book->addKoboSyncedBook($syncedBook);

        // Book was marked as removed before the token
        self::assertTrue($syncResponseHelper->isChangedEntitlement($book));
        self::assertTrue($syncResponseHelper->isArchivedEntitlement($book));
    }

    private function createKoboDevice(): KoboDevice
    {
        return new KoboDevice();
    }

    private function createBook(): Book
    {
        return new Book();
    }

    private function createSyncToken(): SyncToken
    {
        $clock = new TestClock();
        $syncToken = new SyncToken();
        $syncToken->lastCreated = $clock->now();
        $syncToken->lastModified = $clock->now();
        $syncToken->tagLastModified = $clock->now();
        $syncToken->readingStateLastModified = $clock->now();

        return $syncToken;
    }

    private function createKoboSyncedBook(KoboDevice $koboDevice, Book $book): KoboSyncedBook
    {
        $syncedBook = new KoboSyncedBook();
        $syncedBook->setKoboDevice($koboDevice);
        $syncedBook->setBook($book);

        return $syncedBook;
    }
}
