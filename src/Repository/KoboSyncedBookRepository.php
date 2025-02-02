<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\KoboSyncedBook;
use App\Entity\User;
use App\Kobo\SyncToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        $updatedAt = $syncToken->lastModified ?? new \DateTimeImmutable();
        $createdAt = $syncToken->lastCreated ?? new \DateTimeImmutable();

        // Query for all the book to be synced (modified/or new)
        $syncedBooksQuery = $this->createQueryBuilder('koboSyncedBook')
             ->select('book.id')
             ->distinct()
             ->join('koboSyncedBook.book', 'book')
             ->where('koboSyncedBook.koboDevice = :koboDevice')
             ->andWhere('koboSyncedBook.book IN (:books)')
             ->andWhere('koboSyncedBook.archived is null')
             ->setParameter('koboDevice', $koboDevice)
             ->setParameter('books', $books)
        ;

        /** @var int[] $updatedBooksIds */
        $updatedBooksIds = $syncedBooksQuery
            ->getQuery()->getSingleColumnResult();

        // Archived books are deleted from the synced books
        $archivedBooksQueryBuilder = $this->createQueryBuilder('koboSyncedBook');
        $archivedBooksQuery = $archivedBooksQueryBuilder
            ->select('book.id')
            ->distinct()
            ->join('koboSyncedBook.book', 'book')
            ->where('koboSyncedBook.koboDevice = :koboDevice')
            ->andWhere($archivedBooksQueryBuilder->expr()->isNotNull('koboSyncedBook.archived'))
            ->setParameter('koboDevice', $koboDevice->getId());

        /** @var int[] $archivedBooksIds */
        $archivedBooksIds = $archivedBooksQuery
            ->getQuery()->getSingleColumnResult();

        // We delete the archived synced books.
        (clone $archivedBooksQuery)
            ->resetDQLPart('select')
            ->delete()
            ->getQuery()
            ->execute();
        unset($archivedBooksQuery);
        // Note that you might need to refresh $koboDevice..

        // We set the updated date for the synced books
        $syncedBooksQuery->update()
            ->set('koboSyncedBook.updated', ':updatedAt')
            ->setParameter('updatedAt', $updatedAt)
        ->getQuery()
            ->execute();

        // Fetch all the books that we now need to consider as synced
        /** @var array<int, Book> $books */
        $booksToSyncQuery = $this->createQueryBuilder('koboSyncedBook')
            ->resetDQLPart('select')
            ->resetDQLPart('from')
            ->from(Book::class, 'book')
            ->select('book')
            ->where($syncedBooksQuery->expr()->in('book.id', ':booksIds'))
            ->setParameter('booksIds', array_map(fn (Book $book) => $book->getId(), $books));

        if ($updatedBooksIds !== []) {
            $booksToSyncQuery->andWhere($syncedBooksQuery->expr()->notIn('book.id', ':excludedIds'))
                ->setParameter('excludedIds', $updatedBooksIds);
        }

        if ($archivedBooksIds !== []) {
            $booksToSyncQuery->andWhere($syncedBooksQuery->expr()->notIn('book.id', ':removedIds'))
                ->setParameter('removedIds', $archivedBooksIds);
        }

        /** @var Book[] $books */
        $books = $booksToSyncQuery
            ->getQuery()->getResult();

        // We mark the books as synced
        foreach ($books as $book) {
            $object = new KoboSyncedBook($createdAt, $updatedAt, $koboDevice, $book);
            $book->addKoboSyncedBook($object);
            $koboDevice->addKoboSyncedBook($object);
            $this->getEntityManager()->persist($object);
        }

        $this->getEntityManager()->flush();
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

    public function findByUserAndBook(User $user, Book $book): array
    {
        /** @var KoboSyncedBook[] $result */
        $result = $this->createQueryBuilder('kobo_synced_book')
            ->select('kobo_synced_book')
            ->join('kobo_synced_book.koboDevice', 'kobo_device')
            ->join('kobo_device.user', 'user')
            ->where('kobo_device.user = :user')
            ->andWhere('kobo_synced_book.book = :book')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
