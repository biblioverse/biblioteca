<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
            ->getQuery();
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
     * @return array<mixed>
     */
    public function getAllSeries():array
    {
        $qb = $this->createQueryBuilder('serie')
            ->select('serie.serie')
            ->addSelect('serie.serieSlug')
            ->addSelect('COUNT(serie.id) as bookCount')
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
     * @return array<mixed>
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
