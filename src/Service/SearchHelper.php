<?php

namespace App\Service;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use App\Entity\Book;

class SearchHelper
{
    private TypesenseResponse $response;
    public TypesenseQuery $query;

    public function __construct(protected CollectionFinder $bookFinder)
    {
    }

    public function prepareQuery(string $queryBy, ?string $filterBy = null, ?string $sortBy = null, int $perPage = 16, int $page = 1): SearchHelper
    {
        $query = new TypesenseQuery($queryBy, 'title,serie,extension,authors,tags,summary');
        $query->perPage($perPage);
        $query->filterBy($filterBy ?? '');
        $query->sortBy($sortBy ?? '');
        $query->numTypos(2);
        $query->page($page);
        $query->facetBy('authors,serie,tags');
        $this->query = $query;

        return $this;
    }

    public function execute(): SearchHelper
    {
        $this->response = $this->bookFinder->query($this->query);

        return $this;
    }

    /**
     * @return Book[]
     */
    public function getBooks(): array
    {
        $results = $this->response->getResults();

        $books = [];
        foreach ($results as $resultItem) {
            $books[$resultItem->getId()] = $resultItem;
        }

        return $books;
    }

    public function getFacets(): array
    {
        return $this->response->getFacetCounts();
    }

    public function getPagination(): array
    {
        $found = $this->response->getFound();
        $params = $this->query->getParameters();

        $pages = ceil($found / $params['per_page']);

        return [
            'page' => $params['page'],
            'pages' => $pages,
            'perPage' => $params['per_page'],
            'total' => $found,
            'lastPage' => $pages,
            'nextPage' => $params['page'] < $pages ? $params['page'] + 1 : null,
            'previousPage' => $params['page'] > 1 ? $params['page'] - 1 : null,
        ];
    }
}
