<?php

namespace App\Service;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Entity\Book;
use App\Entity\Shelf;
use App\Form\BookFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ShelfManager
{
    use PageFilterFormTrait;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getBooksInShelf(Shelf $shelf): array
    {
        if ($shelf->getQueryString() === null) {
            return $shelf->getBooks()->toArray();
        }

        $qb = $this->entityManager->getRepository(Book::class)->getAllBooksQueryBuilder();

        $request = new Request($shelf->getQueryString());

        $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        $results = $qb->getQuery()->getResult();

        if (!is_array($results)) {
            return [];
        }

        return $results;
    }
}
