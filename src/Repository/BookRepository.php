<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @phpstan-type GroupType array{ item:string, bookCount:int, booksFinished:int }
 */
class BookRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Book::class);
        $this->security = $security;
    }

    public function getAllBooksQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('book')
            ->select('book')
            ->leftJoin('book.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser());
    }

    public function getAllSeries(): Query
    {
        return $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->where('serie.serie IS NOT NULL')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')->getQuery();
    }

    public function getAllPublishers(): Query
    {
        return $this->createQueryBuilder('publisher')
            ->select('publisher.publisher as item')
            ->addSelect('COUNT(publisher.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->where('publisher.publisher IS NOT NULL')
            ->leftJoin('publisher.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('publisher.publisher')->getQuery();
    }

    /**
     * @return GroupType[]
     */
    public function getAllAuthors(): array
    {
        /** @var GroupType[] $results */
        $results = [];
        $qb = $this->createQueryBuilder('author')
            ->select('author.authors as item')
            ->addSelect('COUNT(author.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('author.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('author.authors')
            ->getQuery();

        return $this->convertResults($qb->getResult());
    }

    /**
     * @return GroupType[]
     */
    public function getAllTags(): array
    {
        $qb = $this->createQueryBuilder('tag')
            ->select('tag.tags as item')
            ->addSelect('COUNT(tag.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('tag.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('tag.tags')
            ->getQuery();

        return $this->convertResults($qb->getResult());
    }

    /**
     * @return GroupType[]
     */
    private function convertResults(mixed $intermediateResults): array
    {
        if (!is_array($intermediateResults)) {
            return [];
        }
        $results = [];
        foreach ($intermediateResults as $result) {
            foreach ($result['item'] as $item) {
                if (!array_key_exists($item, $results)) {
                    $results[$item] = [
                        'item' => ucwords(strtolower($item), " \t\r\n\f\v-."),
                        'bookCount' => 0,
                        'booksFinished' => 0,
                    ];
                }
                $results[$item] = [
                    'item' => ucwords(strtolower($item), " \t\r\n\f\v-."),
                    'bookCount' => $result['bookCount'] + $results[$item]['bookCount'],
                    'booksFinished' => $result['booksFinished'] + $results[$item]['booksFinished'],
                ];
            }
        }

        ksort($results);

        return $results;
    }
}
