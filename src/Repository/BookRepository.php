<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Book>
 * @phpstan-type SeriesType array{ serie:string, serieSlug:string, bookCount:int, booksFinished:int, lastBookIndex:int }
 * @phpstan-type AuthorsType array{ mainAuthor:string, authorSlug:string, bookCount:int, booksFinished:int }
*/
class BookRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Book::class);
        $this->security = $security;
    }

    public function getAllBooksQuery():Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->getQuery();
    }
    public function getFavoriteBooksQuery():Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->join('b.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.favorite = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->getQuery();
    }
    public function getUnverifiedBooksQuery():Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.verified = false')
            ->getQuery();
    }
    public function getBooksByReadStatus(bool $read):Query
    {
        $q = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->andWhere('bookInteraction.finished = :read');

        if(!$read){
            $q->orWhere('bookInteraction.finished IS NULL');
        }

            $q->setParameter('user', $this->security->getUser())
            ->setParameter('read', (int)$read);
            ;
        return $q->getQuery();
    }
    public function getByAuthorQuery(string $authorSlug):Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.authorSlug = :authorSlug')
            ->setParameter('authorSlug', $authorSlug)
            ->getQuery();
    }
    public function getBySerieQuery(string $serieSlug):Query
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.serieSlug = :serieSlug')
            ->setParameter('serieSlug', $serieSlug)
            ->addOrderBy('b.serieIndex','ASC')
            ->getQuery();
    }

    /**
     * @param string $query
     * @return array<Book>
     */
    public function search(string $query, int $results=5):array
    {
        $return = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.serie like :query')
            ->orWhere('b.title like :query')
            ->orWhere('b.mainAuthor like :query')
            ->setParameter('query', "%".$query.'%')
            ->setMaxResults($results)
            ->addOrderBy('b.title','ASC')
            ->getQuery()->getResult();
        if(!is_array($return)){
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

    /**
     * @return Query
     */
    public function getAllSeries():Query
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
     * @return Query
     */
    public function getAllAuthors():Query
    {
        $qb = $this->createQueryBuilder('author')
            ->select('author.mainAuthor as item')
            ->addSelect('author.authorSlug as slug')
            ->addSelect('COUNT(author.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('author.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('author.mainAuthor');
        return  $qb->getQuery();
    }
}
