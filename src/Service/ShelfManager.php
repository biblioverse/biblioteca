<?php

namespace App\Service;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use App\Entity\Shelf;

class ShelfManager
{
    public function __construct(protected CollectionFinder $bookFinder)
    {
    }

    public function getBooksInShelf(Shelf $shelf): array
    {
        if ($shelf->getQueryString() === null && $shelf->getQueryFilter() === null) {
            return $shelf->getBooks()->toArray();
        }

        $complexQuery = new TypesenseQuery($shelf->getQueryString() ?? '*', 'title,serie,extension,authors,tags,summary');
        $complexQuery->perPage(200);
        $complexQuery->filterBy($shelf->getQueryFilter() ?? '');
        $complexQuery->sortBy($shelf->getQueryOrder() ?? '');
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
