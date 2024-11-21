<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\KoboDevice;
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
    public function isChangedEntitlement(Book $book, KoboDevice $kobo, SyncToken $syncToken): bool
    {
        if ($this->isNewEntitlement($book, $syncToken)) {
            return false;
        }

        if ($this->isChangedReadingState($book, $kobo, $syncToken)) {
            return false;
        }

        return ($book->getUpdated() instanceof \DateTimeInterface
            && $book->getUpdated() >= $syncToken->lastModified)
            || $book->getCreated() >= $syncToken->lastCreated;
    }

    public function isNewEntitlement(Book $book, SyncToken $syncToken): bool
    {
        return $book->getKoboSyncedBook()->isEmpty() || $book->getCreated() < $syncToken->lastCreated;
    }

    public function isChangedReadingState(Book $book, KoboDevice $kobo, SyncToken $syncToken): bool
    {
        if ($this->isNewEntitlement($book, $syncToken)) {
            return false;
        }
        $lastInteraction = $book->getLastInteraction($kobo->getUser());

        return ($lastInteraction instanceof BookInteraction) && $lastInteraction->getUpdated() >= $syncToken->readingStateLastModified;
    }
}
