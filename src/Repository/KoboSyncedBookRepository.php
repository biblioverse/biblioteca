<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Kobo\SyncToken;
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

    public function updateSyncedBooks(KoboDevice $koboDevice, array $books, SyncToken $syncToken): void
    {
        if ($books === []) {
            return;
        }

        $updatedAt = $syncToken->lastModified ?? new \DateTime();
        $createdAt = $syncToken->lastCreated ?? new \DateTime();

        $qb = $this->createQueryBuilder('koboSyncedBook')
             ->select('book.id')
             ->join('koboSyncedBook.book', 'book')
             ->where('koboSyncedBook.koboDevice = :koboDevice')
             ->andWhere('koboSyncedBook.book IN (:books)')
             ->setParameter('koboDevice', $koboDevice)
             ->setParameter('books', $books)
        ;
        $updatedBooks = (array) $qb
            ->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $qb->update()
            ->set('koboSyncedBook.updated', ':updatedAt')
            ->setParameter('updatedAt', $updatedAt)
        ->getQuery()
            ->execute();

        /** @var array<int, Book> $books */
        $qb1 = $this->createQueryBuilder('koboSyncedBook')
            ->resetDQLPart('select')
            ->resetDQLPart('from')
            ->from(Book::class, 'book')
            ->select('book')
            ->where($qb->expr()->in('book.id', ':booksIds'))
            ->setParameter('booksIds', array_map(fn (Book $book) => $book->getId(), $books));

        if ($updatedBooks !== []) {
            $qb->andWhere($qb->expr()->notIn('book.id', ':excludedIds'))
                ->setParameter('excludedIds', $updatedBooks);
        }

        /** @var Book[] $books */
        $books = $qb1
            ->getQuery()->getResult();

        foreach ($books as $book) {
            $object = new KoboSyncedBook();
            $object->setBook($book);
            $object->setKoboDevice($koboDevice);
            $object->setUpdated($updatedAt);
            $object->setCreated($createdAt);
            $book->addKoboSyncedBook($object);
            $koboDevice->addKoboSyncedBook($object);
            $this->_em->persist($object);
        }

        $this->_em->flush();
    }

    public function deleteAllSyncedBooks(KoboDevice|int $koboDeviceId): int
    {
        $query = $this->createQueryBuilder('koboSyncedBook')
            ->delete()
            ->where('koboSyncedBook.koboDevice = :koboDevice')
            ->setParameter('koboDevice', $koboDeviceId)
            ->getQuery();

        /** @var int $result */
        $result = $query
            ->getResult();

        return $result;
    }

    public function countByKoboDevice(KoboDevice $koboDevice): int
    {
        /** @var int $result */
        $result = $this->createQueryBuilder('koboSyncedBook')
            ->select('count(koboSyncedBook.id)')
            ->where('koboSyncedBook.koboDevice = :koboDevice')
            ->setParameter('koboDevice', $koboDevice)
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }
}
