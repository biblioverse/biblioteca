<?php

namespace App\Tests\Entity;

use App\Entity\KoboSyncedBook;
use App\Tests\TestCaseHelperTrait;
use App\Tests\TestClock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SyncedBookTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    #[\Override]
    public function tearDown(): void
    {
        (new TestClock())->setTime(null);
        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $book = $this->getBook();
        $kobo = $this->getKoboDevice();
        $clock = new TestClock();
        $clock->setTime($clock->now());
        $syncedBook = new KoboSyncedBook($clock->now(), $clock->now(), $kobo, $book);

        self::assertSame($kobo, $syncedBook->getKoboDevice());
        self::assertSame($book, $syncedBook->getBook());
        self::assertSame($clock->now()->getTimestamp(), $syncedBook->getCreated()?->getTimestamp());
        self::assertSame($clock->now()->getTimestamp(), $syncedBook->getUpdated()?->getTimestamp());

        $clock->alter('+1 day');
        $syncedBook->setUpdated($clock->now());
        self::assertSame($clock->now()->getTimestamp(), $syncedBook->getUpdated()->getTimestamp());

        $clock->alter('+1 day');
        $syncedBook->setCreated($clock->now());
        self::assertSame($clock->now()->getTimestamp(), $syncedBook->getCreated()->getTimestamp());

        $clock->alter('+1 day');
        $syncedBook->setArchived($clock->now());
        self::assertSame($clock->now()->getTimestamp(), $syncedBook->getArchived()?->getTimestamp());
    }
}
