<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Kobo\SyncToken;

// Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py

/**
 * @phpstan-type BookEntitlement array<string, mixed>
 * @phpstan-type BookMetadata array<string, mixed>
 * @phpstan-type BookReadingState array<string, mixed>
 * @phpstan-type BookTag array<string, mixed>
 */
class SyncResponseHelper
{
    public function __construct(private readonly SyncToken $syncToken, private readonly KoboDevice $koboDevice)
    {
    }

    public function isChangedEntitlement(Book $book): bool
    {
        if ($this->isNewEntitlement($book)) {
            return false;
        }

        $isDeleted = $this->isArchivedEntitlement($book);
        if ($isDeleted) {
            return true;
        }

        if (!$this->syncToken->lastModified instanceof \DateTimeInterface) {
            return false;
        }

        return $book->getUpdated() >= $this->syncToken->lastModified;
    }

    public function isNewEntitlement(Book $book): bool
    {
        if ($book->getKoboSyncedBook()->isEmpty()) {
            return true;
        }

        return $this->syncToken->lastCreated instanceof \DateTimeInterface && $book->getCreated() > $this->syncToken->lastCreated;
    }

    public function isChangedReadingState(Book $book): bool
    {
        if ($this->isChangedEntitlement($book)) {
            return false;
        }

        if (!$this->syncToken->readingStateLastModified instanceof \DateTimeInterface) {
            return false;
        }

        $lastInteraction = $book->getLastInteraction($this->koboDevice->getUser());

        return ($lastInteraction instanceof BookInteraction) && $lastInteraction->getUpdated() >= $this->syncToken->readingStateLastModified;
    }

    public function isNewTag(Shelf $shelf): bool
    {
        if (!$this->syncToken->tagLastModified instanceof \DateTimeInterface) {
            return true;
        }

        return $shelf->getCreated() >= $this->syncToken->tagLastModified;
    }

    public function isChangedTag(Shelf $shelf): bool
    {
        if ($this->isNewTag($shelf)) {
            return false;
        }

        return $shelf->getUpdated() >= $this->syncToken->tagLastModified;
    }

    public function isArchivedEntitlement(Book $book): bool
    {
        foreach ($book->getKoboSyncedBook() as $syncedBook) {
            if ($syncedBook->getKoboDevice() === $this->koboDevice && $syncedBook->isArchived()) {
                return true;
            }
        }

        return false;
    }
}
