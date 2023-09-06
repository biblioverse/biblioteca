<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    public function getAllBooksQuery(): Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->getQuery();
    }

    public function getFavoriteBooksQuery(): Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->join('b.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.favorite = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->getQuery();
    }

    public function getUnverifiedBooksQuery(): Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.verified = false')
            ->getQuery();
    }

    public function getBooksByReadStatus(bool $read): Query
    {
        $q = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->andWhere('bookInteraction.finished = :read');

        if (!$read) {
            $q->orWhere('bookInteraction.finished IS NULL');
        }

        $q->setParameter('user', $this->security->getUser())
        ->setParameter('read', (int) $read);

        return $q->getQuery();
    }

    public function getByAuthorQuery(string $author): Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('JSON_CONTAINS(b.authors, :author)=1')
            ->setParameter('author', json_encode([$author]))
            ->getQuery();
    }

    public function getBySerieQuery(string $serieSlug): Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.serieSlug = :serieSlug')
            ->setParameter('serieSlug', $serieSlug)
            ->addOrderBy('b.serieIndex', 'ASC')
            ->getQuery();
    }

    /**
     * @return array<Book>
     */
    public function search(string $query, int $results = 5): array
    {
        $return = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.serie like :query')
            ->orWhere('b.title like :query')
            ->orWhere('JSON_CONTAINS(b.authors, :author)=1')
            ->setParameter('author', json_encode([$query]))
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($results)
            ->addOrderBy('b.title', 'ASC')
            ->getQuery()->getResult();
        if (!is_array($return)) {
            return [];
        }

        return $return;
    }

    public function save(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllSeries(): Query
    {
        return $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('serie.serieSlug as slug')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->where('serie.serie IS NOT NULL')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')->getQuery();
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
                        'item' => $item,
                        'bookCount' => 0,
                        'booksFinished' => 0,
                    ];
                }
                $results[$item] = [
                    'item' => $item,
                    'bookCount' => $result['bookCount'] + $results[$item]['bookCount'],
                    'booksFinished' => $result['booksFinished'] + $results[$item]['booksFinished'],
                ];
            }
        }

        ksort($results);

        return $results;
    }
}
