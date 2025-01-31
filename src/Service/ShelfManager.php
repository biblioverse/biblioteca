<?php

namespace App\Service;

use App\Entity\Shelf;
use App\Service\Search\SearchHelper;

class ShelfManager
{
    public function __construct(protected SearchHelper $searchHelper)
    {
    }

    public function getBooksInShelf(Shelf $shelf): array
    {
        if ($shelf->getQueryString() === null && $shelf->getQueryFilter() === null) {
            return $shelf->getBooks()->toArray();
        }

        $this->searchHelper->prepareQuery(
            $shelf->getQueryString() ?? '*',
            $shelf->getQueryFilter() ?? '',
            $shelf->getQueryOrder() ?? '',
            200
        )->execute();

        return $this->searchHelper->getBooks();
    }

    public function getBooksInShelves(array $shelves): array
    {
        $books = [];
        foreach ($shelves as $shelf) {
            // TODO: Use multi-search (when ready) to avoid many queries
            $shelfBooks = $this->getBooksInShelf($shelf);
            foreach ($shelfBooks as $book) {
                $books[$book->getId()] = $book;
            }
        }

        return $books;
    }
}
