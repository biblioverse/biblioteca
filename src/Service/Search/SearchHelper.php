<?php

namespace App\Service\Search;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Prompt\SearchHintPrompt;
use App\Entity\Book;
use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMappingInterface;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;
use Biblioverse\TypesenseBundle\Search\SearchCollectionInterface;

class SearchHelper
{
    /** @var ?SearchResultsHydrated<Book> */
    public ?SearchResultsHydrated $response = null;
    public ?SearchQuery $query = null;
    public int $maxFacetValues = 10;

    /**
     * @param SearchCollectionInterface<Book> $searchBooks
     */
    public function __construct(
        protected SearchCollectionInterface $searchBooks,
        protected MappingGeneratorInterface $mappingGeneratorBooks,
        private readonly CommunicatorDefiner $communicatorDefiner,
    ) {
    }

    /**
     * Return the fields available for filtering
     * @return string[]
     */
    public function getBookFieldsForQuery(): array
    {
        $fields = $this->mappingGeneratorBooks->getMapping()->getFields();
        $fieldsName = array_map(fn (FieldMappingInterface $fieldMapping) => $fieldMapping->getName(), $fields);

        return array_filter($fieldsName, fn (string $field) => !in_array($field, ['id', 'created_at', 'updated_at', 'sortable_id'], true));
    }

    public function prepareQuery(string $q, ?string $filterBy = null, ?string $sortBy = null, int $perPage = 16, int $page = 1): SearchHelper
    {
        $this->query = new SearchQuery(
            q: $q,
            queryBy: 'title,serie,extension,authors,tags,summary',
            filterBy: $filterBy,
            sortBy: $sortBy,
            maxFacetValues: $this->maxFacetValues,
            numTypos: 2,
            page: $page,
            perPage: $perPage,
            excludeFields: 'embedding',
            facetBy: 'authors,serie,tags,age',
            facetStrategy: 'exhaustive',
        );

        return $this;
    }

    public function execute(): SearchHelper
    {
        if (!$this->query instanceof SearchQuery) {
            return $this;
        }
        $this->response = $this->searchBooks->search($this->query);

        return $this;
    }

    /**
     * @return Book[]
     */
    public function getBooks(): array
    {
        if (!$this->response instanceof SearchResultsHydrated) {
            return [];
        }

        return $this->response->getResults();
    }

    public function getFacets(): array
    {
        if (!$this->response instanceof SearchResultsHydrated) {
            return [];
        }

        return $this->response->getFacetCounts();
    }

    public function getPagination(): array
    {
        if (!$this->response instanceof SearchResultsHydrated) {
            return [
                'page' => 1,
                'total' => 0,
                'lastPage' => 1,
                'nextPage' => null,
                'previousPage' => null,
            ];
        }

        $perPage = $this->response->getPerPage() ?? 1;
        $totalPages = $this->response->getTotalPage();

        return [
            'page' => $this->response->getPage(),
            'pages' => $this->response->getTotalPage(),
            'perPage' => $perPage,
            'total' => $this->response->getFound(),
            'lastPage' => $totalPages,
            'nextPage' => $this->response->getPage() < $totalPages ? ($this->response->getPage() ?? 1) + 1 : null,
            'previousPage' => $this->response->getPage() > 1 ? $this->response->getPage() - 1 : null,
        ];
    }

    public function getQueryHints(): ?array
    {
        $communicator = $this->communicatorDefiner->getCommunicator(AiAction::Search);
        if (!$communicator instanceof AiCommunicatorInterface || !$this->query instanceof SearchQuery) {
            return null;
        }

        $q = $this->query->getQ();
        if ($q === '') {
            return null;
        }

        $facets = $this->getFacets();
        $facets = array_column($facets, 'counts', 'field_name');
        foreach ($facets as $key => $value) {
            array_walk($facets[$key], function (&$value) {$value = $value['value']; });
        }
        $prompt = new SearchHintPrompt();

        $communicator->getAiModel()->setSystemPrompt($prompt->getTypesenseNaturalLanguagePrompt($facets['serie'], $facets['authors'], $facets['tags']));

        $communicator->initialise($communicator->getAiModel());

        $prompt->setPrompt('### User-Supplied Query ###
'.$q);
        $result = $communicator->interrogate($prompt->getPrompt());

        return $prompt->convertResult($result);
    }
}
