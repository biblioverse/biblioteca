<?php

namespace App\Service;

use ACSEO\TypesenseBundle\Finder\SpecificCollectionFinder;
use Kiwilan\Ebook\EbookCover;
use Kiwilan\Ebook\Models\BookAuthor;

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?int, cover: ?EbookCover }
 */
class BookSearch
{
    public function __construct(private readonly SpecificCollectionFinder $autocompleteBookFinder)
    {
    }

    public function autocomplete(string $query): array
    {
        return $this->autocompleteBookFinder->search($query)->getRawResults();
    }

    public function facets(string $query): array
    {
        return $this->autocompleteBookFinder->search($query)->getFacetCounts();
    }
}
