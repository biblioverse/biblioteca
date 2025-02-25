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
        $this->searchHelper->queries = [];
        foreach ($shelves as $shelf) {
            if ($shelf->getQueryString() === null && $shelf->getQueryFilter() === null) {
                continue;
            }
            $this->searchHelper->prepareMultiQuery(
                $shelf->getQueryString() ?? '*',
                $shelf->getQueryFilter() ?? '',
                $shelf->getQueryOrder() ?? '',
                200
            );
        }

        $this->searchHelper->executeMultiSearch();
        foreach ($this->searchHelper->getBooks() as $book) {
            $books[$book->getId()] = $book;
        }

        return $books;
    }
}
