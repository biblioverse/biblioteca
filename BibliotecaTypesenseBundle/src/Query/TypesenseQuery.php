<?php

declare(strict_types=1);

namespace Biblioteca\TypesenseBundle\Query;

class TypesenseQuery
{
    /**
     * @var array<string, mixed>
     */
    private array $searchParameters;

    public function __construct(?string $q = null, ?string $queryBy = null)
    {
        $this->searchParameters = [];
        if ($q !== null) {
            $this->addParameter('q', $q);
        }
        if ($queryBy !== null) {
            $this->addParameter('query_by', $queryBy);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->searchParameters;
    }

    public function hasParameter($key): bool
    {
        return isset($this->searchParameters[$key]);
    }

    /**
     * Maximum number of hits returned. Increasing this value might increase search latency. Use all to return all hits found.
     *
     * @param [type] $maxHits
     */
    public function maxHits($maxHits): self
    {
        return $this->addParameter('max_hits', $maxHits);
    }

    /**
     * Boolean field to indicate that the last word in the query should be treated as a prefix, and not as a whole word. This is necessary for building autocomplete and instant search interfaces.
     */
    public function prefix(bool $prefix): self
    {
        return $this->addParameter('prefix', $prefix);
    }

    /**
     * Filter conditions for refining your search results. A field can be matched against one or more values.
     */
    public function filterBy(string $filterBy): self
    {
        return $this->addParameter('filter_by', $filterBy);
    }

    /**
     * A list of numerical fields and their corresponding sort orders that will be used for ordering your results. Separate multiple fields with a comma. Upto 3 sort fields can be specified.
     */
    public function sortBy(string $sortBy): self
    {
        return $this->addParameter('sort_by', $sortBy);
    }

    /**
     * A list of fields that will be used for faceting your results on. Separate multiple fields with a comma.
     */
    public function facetBy(string $facetBy): self
    {
        return $this->addParameter('facet_by', $facetBy);
    }

    /**
     * Maximum number of facet values to be returned.
     */
    public function maxFacetValues(int $maxFacetValues): self
    {
        return $this->addParameter('max_facet_values', $maxFacetValues);
    }

    /**
     * Facet values that are returned can now be filtered via this parameter. The matching facet text is also highlighted. For example, when faceting by category, you can set facet_query=category:shoe to return only facet values that contain the prefix "shoe".
     */
    public function facetQuery(string $facetQuery): self
    {
        return $this->addParameter('facet_query', $facetQuery);
    }

    /**
     * Number of typographical errors (1 or 2) that would be tolerated.
     */
    public function numTypos(int $numTypos): self
    {
        return $this->addParameter('num_typos', $numTypos);
    }

    /**
     * Results from this specific page number would be fetched.
     */
    public function page(int $page): self
    {
        return $this->addParameter('page', $page);
    }

    /**
     * Number of results to fetch per page.
     */
    public function perPage(int $perPage): self
    {
        return $this->addParameter('per_page', $perPage);
    }

    /**
     * You can aggregate search results into groups or buckets by specify one or more group_by fields. Separate multiple fields with a comma.
     */
    public function groupBy(string $groupBy): self
    {
        return $this->addParameter('group_by', $groupBy);
    }

    /**
     * Maximum number of hits to be returned for every group. If the group_limit is set as K then only the top K hits in each group are returned in the response.
     */
    public function groupLimit(int $groupLimit): self
    {
        return $this->addParameter('group_limit', $groupLimit);
    }

    /**
     * Comma-separated list of fields from the document to include in the search result.
     */
    public function includeFields(string $includeFields): self
    {
        return $this->addParameter('include_fields', $includeFields);
    }

    /**
     * Comma-separated list of fields from the document to exclude in the search result.
     */
    public function excludeFields(string $excludeFields): self
    {
        return $this->addParameter('exclude_fields', $excludeFields);
    }

    /**
     * Comma separated list of fields which should be highlighted fully without snippeting.
     */
    public function highlightFullFields(string $highlightFullFields): self
    {
        return $this->addParameter('highlight_full_fields', $highlightFullFields);
    }

    /**
     * Field values under this length will be fully highlighted, instead of showing a snippet of relevant portion.
     */
    public function snippetThreshold(int $snippetThreshold): self
    {
        return $this->addParameter('snippet_threshold', $snippetThreshold);
    }

    /**
     * If the number of results found for a specific query is less than this number, Typesense will attempt to drop the tokens in the query until enough results are found. Tokens that have the least individual hits are dropped first. Set drop_tokens_threshold to 0 to disable dropping of tokens.
     */
    public function dropTokensThreshold(int $dropTokensThreshold): self
    {
        return $this->addParameter('drop_tokens_threshold', $dropTokensThreshold);
    }

    /**
     * If the number of results found for a specific query is less than this number, Typesense will attempt to look for tokens with more typos until enough results are found.
     */
    public function typoTokensThreshold(int $typoTokensThreshold): self
    {
        return $this->addParameter('typo_tokens_threshold', $typoTokensThreshold);
    }

    /**
     * A list of records to unconditionally include in the search results at specific positions.
     * An example use case would be to feature or promote certain items on the top of search results.
     * A comma separated list of record_id:hit_position. Eg: to include a record with ID 123 at Position 1 and another record with ID 456 at Position 5, you'd specify 123:1,456:5.
     * You could also use the Overrides feature to override search results based on rules. Overrides are applied first, followed by pinned_hits and finally hidden_hits.
     */
    public function pinnedHits(string $pinnedHits): self
    {
        return $this->addParameter('pinned_hits', $pinnedHits);
    }

    /**
     * A list of records to unconditionally hide from search results.
     * A comma separated list of record_ids to hide. Eg: to hide records with IDs 123 and 456, you'd specify 123,456.
     * You could also use the Overrides feature to override search results based on rules. Overrides are applied first, followed by pinned_hits and finally hidden_hits.
     */
    public function hiddenHits(string $hiddenHits): self
    {
        return $this->addParameter('hidden_hits', $hiddenHits);
    }

    /**
     * Generic method that allows to add any parameter to the TypesenseQuery.
     */
    public function addParameter(string $key, mixed $value): self
    {
        $this->searchParameters[$key] = $value;

        return $this;
    }
}
