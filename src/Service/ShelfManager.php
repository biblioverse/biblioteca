<?php

namespace App\Service;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use App\Entity\Shelf;
use App\Search\QueryTokenizer;
use App\Search\TypesenseTokenHandler;

class ShelfManager
{
    public function __construct(protected CollectionFinder $bookFinder, private readonly QueryTokenizer $queryTokenizer, private readonly TypesenseTokenHandler $tokenHandler)
    {
    }

    public function getBooksInShelf(Shelf $shelf): array
    {
        if ($shelf->getQueryString() === null) {
            return $shelf->getBooks()->toArray();
        }

        $tokens = $this->queryTokenizer->tokenize($shelf->getQueryString());
        $complexQuery = $this->tokenHandler->handle($tokens);
        $complexQuery->perPage(200);
        $complexQuery->sortBy('serieIndex:asc');
        $complexQuery->numTypos(2);
        $complexQuery->page(1);

        $results = $this->bookFinder->query($complexQuery)->getResults();
        $books = [];
        foreach ($results as $resultItem) {
            $books[$resultItem->getId()] = $resultItem;
        }

        return $books;
    }
}
