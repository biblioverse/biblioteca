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
    public function isChangedEntitlement(Book $book, SyncToken $syncToken): bool
    {
        if ($this->isNewEntitlement($book, $syncToken)) {
            return false;
        }

        if (!$syncToken->lastModified instanceof \DateTimeInterface) {
            return false;
        }

        return $book->getUpdated() >= $syncToken->lastModified;
    }

    public function isNewEntitlement(Book $book, SyncToken $syncToken): bool
    {
        return $book->getKoboSyncedBook()->isEmpty(); // $book->getCreated() >= $syncToken->lastCreated;
    }

    public function isChangedReadingState(Book $book, KoboDevice $koboDevice, SyncToken $syncToken): bool
    {
        if ($this->isChangedEntitlement($book, $syncToken)) {
            return false;
        }

        if (!$syncToken->readingStateLastModified instanceof \DateTimeInterface) {
            return false;
        }

        $lastInteraction = $book->getLastInteraction($koboDevice->getUser());

        return ($lastInteraction instanceof BookInteraction) && $lastInteraction->getUpdated() >= $syncToken->readingStateLastModified;
    }

    public function isNewTag(Shelf $shelf, SyncToken $syncToken): bool
    {
        if (!$syncToken->lastCreated instanceof \DateTimeInterface) {
            return true;
        }

        return $shelf->getCreated() >= $syncToken->lastCreated;
    }

    public function isChangedTag(Shelf $shelf, SyncToken $syncToken): bool
    {
        return $syncToken->tagLastModified instanceof \DateTimeInterface && $shelf->getUpdated() >= $syncToken->tagLastModified;
    }
}
