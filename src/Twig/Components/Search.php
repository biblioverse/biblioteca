<?php

namespace App\Twig\Components;

use App\Entity\Book;
use Biblioteca\TypesenseBundle\Query\SearchQuery;
use Biblioteca\TypesenseBundle\Search\Results\SearchResultsHydrated;
use Biblioteca\TypesenseBundle\Search\SearchCollectionInterface as TypesenseSearch;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent(method: 'get')]
class Search
{
    #[LiveProp(writable: true, url: true)]
    public string $query = '';
    /** @var array<int|string, Book> */
    public array $books = [];
    public array $facets = [];
    public array $highlights = [];

    /**
     * @param TypesenseSearch<Book> $searchBooks
     */
    public function __construct(
        protected TypesenseSearch $searchBooks,
    ) {
    }

    #[LiveAction]
    public function addToQuery(#[LiveArg] string $value): void
    {
        $this->query = $value.' '.$this->query;
    }

    public function __invoke(): void
    {
        $this->books = [];
        $this->facets = [];
        $this->highlights = [];

        if ('' === $this->query) {
            return;
        }
        /** @var SearchResultsHydrated<Book> $results */
        $results = $this->searchBooks->search(new SearchQuery(
            q: $this->query,
            queryBy: 'title,authors,serie,tags,summary,serieIndex',
            facetBy: 'authors,serie,tags',
            numTypos: 2,
            perPage: 16,
        ));

        $this->books = [];
        foreach ($results as $book) {
            $this->books[$book->getId()] = $book;
        }

        $this->highlights = $results->getHighlight();

        $this->facets = $results->getFacetCounts();
    }
}
