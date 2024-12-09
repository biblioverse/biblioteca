<?php

namespace App\Service;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Entity\Shelf;
use App\Form\BookFilterType;
use App\Repository\BookRepository;
use Kiwilan\Ebook\EbookCover;
use Kiwilan\Ebook\Models\BookAuthor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?float, cover: ?EbookCover }
 */
class ShelfManager
{
    use PageFilterFormTrait;

    public function __construct(private BookRepository $bookRepository)
    {
    }

    public function getBooksInShelf(Shelf $shelf): array
    {
        if ($shelf->getQueryString() === null) {
            return $shelf->getBooks()->toArray();
        }

        $qb = $this->bookRepository->getAllBooksQueryBuilder();

        $request = new Request($shelf->getQueryString());

        $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        $results = $qb->getQuery()->getResult();

        if (!is_array($results)) {
            return [];
        }

        return $results;
    }
}
