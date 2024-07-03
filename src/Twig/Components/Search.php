<?php

namespace App\Twig\Components;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use App\Entity\Book;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(method: 'get')]
class Search
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';
    public array $books = [];

    public function __construct(protected CollectionFinder $bookFinder)
    {
    }

    #[LiveAction]
    public function addToQuery(#[LiveArg] string $value): void
    {
        $this->query = $value.' '.$this->query;
    }

    /**
     * @return array<Book>
     */
    public function getResults(): array
    {
        if ('' === $this->query) {
            return [];
        }

        $complexQuery = new TypesenseQuery($this->query, 'title,authors,serie,tags,summary');

        $complexQuery->facetBy('authors,serie,tags');

        $complexQuery->perPage(16);
        $complexQuery->sortBy('serieIndex:ASC');
        $complexQuery->numTypos(2);

        $rawResults = $this->bookFinder->rawQuery($complexQuery)->getRawResults();
        $results = $this->bookFinder->query($complexQuery)->getResults();
        $facets = $this->bookFinder->query($complexQuery)->getFacetCounts();

        foreach ($rawResults as $result) {
            $document = $result['document'];
            foreach ($result['highlights'] as $highlight) {
                $document[$highlight['field']] = $highlight['snippet'] ?? '';
            }
            $this->books[$document['id']] = $document;
        }
        foreach ($results as $result) {
            $this->books[$result->getId()]['book'] = $result;
        }

        return ['books' => $this->books, 'facets' => $facets];
    }
}
