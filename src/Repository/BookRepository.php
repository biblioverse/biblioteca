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
     * @return array<SeriesType>
     */
    public function getAllSeries():array
    {
        $qb = $this->createQueryBuilder('serie')
            ->select('serie.serie')
            ->addSelect('serie.serieSlug')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')//fixme
            ->where('serie.serie IS NOT NULL')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie');
        $return =  $qb->getQuery()->getResult();
        if(!is_array($return)){
            return [];
        }
        return $return;

    }

    /**
     * @return array<AuthorsType>
     */
    public function getAllAuthors():array
    {
        $qb = $this->createQueryBuilder('author')
            ->select('author.mainAuthor')
            ->addSelect('author.authorSlug')
            ->addSelect('COUNT(author.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')//fixme
            ->leftJoin('author.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('author.mainAuthor');
        $return =  $qb->getQuery()->getResult();
        if(!is_array($return)){
            return [];
        }
        return $return;

    }
}
