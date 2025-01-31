<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Shelf;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\KoboSyncedBookRepository;
use App\Repository\ShelfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service to archive or un-archive a book (from the KoboDevice)
 */
class BookArchiver
{
    public function __construct(
        private readonly KoboSyncedBookRepository $syncedBookRepository,
        private readonly Security $security,
        private readonly BookRepository $bookRepository,
        private readonly ShelfRepository $shelfRepository,
        private readonly ShelfManager $shelfManager,
    ) {
    }

    public function archiveBookFromShelf(Shelf $shelf, Book $book): void
    {
        $this->setArchived($shelf, $book, new \DateTimeImmutable());
    }

    public function unArchiveBookFromShelf(Shelf $shelf, Book $book): void
    {
        $this->setArchived($shelf, $book, null);
    }

    private function setArchived(Shelf $shelf, Book $book, ?\DateTimeImmutable $archiveDate): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        // If no synced book exists, nothing to do
        $syncedBooks = $this->syncedBookRepository->findByUserAndBook($user, $book);
        if ($syncedBooks === []) {
            return;
        }

        // If the shelf is not synced with kobo, nothing to do
        $shelves = new ArrayCollection($this->shelfRepository->findByUserSyncedWithKobos($user));
        if (!$shelves->contains($shelf)) {
            return;
        }

        // We can not archive a book if another synced shelf uses it.
        $nbStaticShelves = $this->bookRepository->inHowManyStaticKoboShelves($book, $user);
        if ($archiveDate instanceof \DateTimeImmutable && $nbStaticShelves > 0) {
            return;
        }

        // For dynamic shelf, we need to check if the book is inside the shelf
        $dynamicShelves = $shelves->filter(fn (Shelf $s) => $s->getQueryString() !== null || $s->getQueryFilter() !== null);
        $dynamicShelves = $dynamicShelves->filter(fn (Shelf $s) => $shelf !== $s);
        $books = $this->shelfManager->getBooksInShelves($dynamicShelves->toArray());
        // Don't archive if the book is used inside a dynamic shelf
        if ($books !== [] && $archiveDate instanceof \DateTimeImmutable) {
            return;
        }

        // Ok, we can now archive/un-archive the synced-book safely
        foreach ($syncedBooks as $syncedBook) {
            // Nothing to do if already archived (Avoid changing the date)
            if ($archiveDate instanceof \DateTimeImmutable && $syncedBook->isArchived()) {
                continue;
            }
            $syncedBook->setArchived($archiveDate);
        }

        $this->syncedBookRepository->flush();
    }
}
