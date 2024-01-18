<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\KoboSyncedBook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KoboSyncedBook>
 *
 * @method KoboSyncedBook|null find($id, $lockMode = null, $lockVersion = null)
 * @method KoboSyncedBook|null findOneBy(array $criteria, array $orderBy = null)
 * @method KoboSyncedBook[] findAll()
 * @method KoboSyncedBook[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KoboSyncedBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KoboSyncedBook::class);
    }

    //    /**
    //     * @return KoboSyncedBook[] Returns an array of KoboSyncedBook objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('k.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?KoboSyncedBook
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function updateSyncedBooks(\App\Entity\Kobo $kobo, array $books, \App\Kobo\SyncToken $syncToken): void
    {
        $updatedAt = $syncToken->lastModified ?? new \DateTime();

        $qb = $this->createQueryBuilder('koboSyncedBook')
             ->select('book.id')
             ->join('koboSyncedBook.book', 'book')
             ->where('koboSyncedBook.kobo = :kobo')
             ->andWhere('koboSyncedBook.book IN (:books)')
             ->setParameter('kobo', $kobo)
             ->setParameter('books', $books)
        ;
        $updatedBooks = (array) $qb
            ->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $qb->update()
            ->set('koboSyncedBook.created', ':updatedA')
            ->setParameter('updatedA', $updatedAt)
        ->getQuery()
            ->execute();

        /** @var array<int, Book> $books */
        $qb1 = $this->createQueryBuilder('koboSyncedBook')
            ->resetDQLPart('select')
            ->resetDQLPart('from')
            ->from(Book::class, 'book')
            ->select('book')
            ->where($qb->expr()->in('book.id', ':booksIds'))
            ->setParameter('booksIds', array_map(function (Book $book) {
                return $book->getId();
            }, $books));

        if (count($updatedBooks) > 0) {
            $qb->andWhere($qb->expr()->notIn('book.id', ':excludedIds'))
                ->setParameter('excludedIds', $updatedBooks);
        }

        /** @var Book[] $books */
        $books = $qb1
            ->getQuery()->getResult();

        foreach ($books as $book) {
            $object = new KoboSyncedBook();
            $object->setBook($book);
            $object->setKobo($kobo);
            $object->setUpdated($updatedAt);
            $object->setCreated($updatedAt);
            $book->addKoboSyncedBook($object);
            $kobo->addKoboSyncedBook($object);
            $this->_em->persist($object);
        }
        $this->_em->flush();
    }
}
