<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\KoboDevice;
use App\Entity\User;
use App\Kobo\SyncToken;
use App\Service\ShelfManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @phpstan-type UnconvertedGroupType array{ item:string|array, bookCount:int, booksFinished:int }
 * @phpstan-type GroupType array{ item:string, bookCount:int, booksFinished:int }
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security, private readonly ShelfManager $shelfManager)
    {
        parent::__construct($registry, Book::class);
    }

    public function getAllBooksQueryBuilder(): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('book')
            ->select('book')
            ->leftJoin('book.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->setParameter('user', $user);

        if ($user instanceof User) {
            $qb->andWhere('COALESCE(book.ageCategory,1) <= COALESCE(:ageCategory,10)');
            $qb->setParameter('ageCategory', $user->getMaxAgeCategory());
        }

        return $qb;
    }

    public function getReadBooks(?string $year, string $type): QueryBuilder
    {
        $qb = $this->createQueryBuilder('book')
            ->select('book')
            ->leftJoin('book.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->where('bookInteraction.finished = true')
            ->orderBy('bookInteraction.finishedDate', 'DESC')
            ->setParameter('user', $this->security->getUser());

        if ($year === null) {
            $qb->andWhere('bookInteraction.finishedDate is null');
        } else {
            $qb->andWhere('YEAR(bookInteraction.finishedDate) = :year')
                ->setParameter('year', $year);
        }
        $qb->andWhere('book.extension in(:type)')
            ->setParameter('type', self::extensionsFromType($type));

        return $qb;
    }

    public static function extensionToType(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'pdf',
            'epub', 'mobi' => 'book',
            'cbr', 'cba', 'cbz', 'cbt', 'cb7' => 'comic',
            default => 'unknown-'.$extension,
        };
    }

    public static function extensionsFromType(string $type): array
    {
        if ($type === 'all') {
            return ['pdf', 'epub', 'mobi', 'cbr', 'cba', 'cbz', 'cbt', 'cb7'];
        }

        return match (strtolower($type)) {
            'pdf' => ['pdf'],
            'book' => ['epub', 'mobi'],
            'comic' => ['cbr', 'cba', 'cbz', 'cbt', 'cb7'],
            default => [],
        };
    }

    public function getReadTypes(): array
    {
        $qb = $this->createQueryBuilder('book')
            ->select('book.extension')
            ->distinct(true)
            ->leftJoin('book.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->where('bookInteraction.finished = true')
            ->orderBy('bookInteraction.finishedDate', 'DESC')
            ->setParameter('user', $this->security->getUser());
        $extensions = $qb->getQuery()->getResult();
        if (!is_array($extensions)) {
            return [];
        }
        $extensions = array_column($extensions, 'extension');
        $types = [];
        foreach ($extensions as $extension) {
            $type = self::extensionToType($extension);
            $types[$type] = $type;
        }

        return $types;
    }

    public function getReadYears(): array
    {
        $qb = $this->createQueryBuilder('book')
            ->select('YEAR(bookInteraction.finishedDate) as year')
            ->distinct(true)
            ->leftJoin('book.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.user=:user')
            ->where('bookInteraction.finished = true')
            ->orderBy('bookInteraction.finishedDate', 'DESC')
            ->setParameter('user', $this->security->getUser());

        $results = $qb->getQuery()->getResult();
        if (!is_array($results)) {
            return [];
        }

        return array_column($results, 'year');
    }

    public function countBooks(bool $group = false): array
    {
        $qb = $this->createQueryBuilder('book')
            ->select('book.extension, count(book.extension) as nb')
            ->groupBy('book.extension')
            ->distinct(true);

        /** @var array{extension:string,nb:int}[] */
        $results = $qb->getQuery()->getResult();

        $types = [];
        if ($group) {
            foreach ($results as $result) {
                $type = self::extensionToType($result['extension']);
                $types[$type] = ($types[$type] ?? 0) + $result['nb'];
            }
        } else {
            foreach ($results as $result) {
                $types[$result['extension']] = ($types[$result['extension']] ?? 0) + $result['nb'];
            }
        }

        return $types;
    }

    /**
     * @return Book[]
     */
    public function getWithSameAuthors(Book $book, int $maxResults = 6): array
    {
        try {
            $qb = $this->getAllBooksQueryBuilder();

            $orModule = $qb->expr()->orX();

            foreach ($book->getAuthors() as $key => $author) {
                $orModule->add('JSON_CONTAINS(lower(book.authors), :author'.$key.')=1');
                $qb->setParameter('author'.$key, json_encode([strtolower($author)]));
            }
            $qb->andWhere($orModule);

            /** @var Book[] $results */
            $results = $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            if ($e->getMessage() === "Operation 'JSON_CONTAINS' is not supported by platform.") {
                return [];
            }
            throw $e;
        }

        $items = array_filter($results, static fn ($result) => $result->getId() !== $book->getId() && ($result->getSerie() === null || $book->getSerie() !== $result->getSerie()));

        if ($items === []) {
            return [];
        }

        $randed = array_rand($items, min(count($items), $maxResults));
        if (!is_array($randed)) {
            $randed = [$randed];
        }

        return array_filter($items, static fn ($key) => in_array($key, $randed, true), ARRAY_FILTER_USE_KEY);
    }

    public function findByUuid(string $uuid): ?Book
    {
        $qb = $this->getAllBooksQueryBuilder();
        $qb->andWhere('book.uuid = :uuid')
            ->setParameter('uuid', $uuid);

        /** @var Book|null $book */
        $book = $qb->getQuery()
            ->getOneOrNullResult();

        return $book;
    }

    /**
     * @return Book[]
     */
    public function findByAuthor(string $author): array
    {
        $qb = $this->getAllBooksQueryBuilder();

        $orModule = $qb->expr()->orX();

        $orModule->add('JSON_CONTAINS(lower(book.authors), :author)=1');
        $qb->setParameter('author', json_encode([strtolower($author)]));

        $qb->andWhere($orModule);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Book[]
     */
    public function findByTag(string $tag, ?int $limit = null): array
    {
        $qb = $this->getAllBooksQueryBuilder();

        $orModule = $qb->expr()->orX();

        $orModule->add('JSON_CONTAINS(lower(book.tags), :tag)=1');
        $qb->setParameter('tag', json_encode([strtolower($tag)]));

        $qb->andWhere($orModule);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $qb->andWhere('COALESCE(book.ageCategory,1) <= COALESCE(:ageCategory,10)');
            $qb->setParameter('ageCategory', $user->getMaxAgeCategory());
        }

        return $qb->getQuery()->getResult();
    }

    public function getAllSeries(): Query
    {
        return $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->where('serie.serie IS NOT NULL')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')->getQuery();
    }

    public function getIncompleteSeries(): Query
    {
        return $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user= :user')
            ->where('serie.serie IS NOT NULL')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')
            ->having('count(serie.id) != max(serie.serieIndex)')
            ->getQuery();
    }

    public function getStartedSeries(): Query
    {
        return $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user= :user')
            ->where('serie.serie IS NOT NULL')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')
            ->having('COUNT(bookInteraction.finished)>0 AND COUNT(bookInteraction.finished)<MAX(serie.serieIndex)')
            ->getQuery();
    }

    public function getAllPublishers(): Query
    {
        return $this->createQueryBuilder('publisher')
            ->select('publisher.publisher as item')
            ->addSelect('COUNT(publisher.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->where('publisher.publisher IS NOT NULL')
            ->leftJoin('publisher.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user= :user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('publisher.publisher')->getQuery();
    }

    /**
     * @return GroupType[]
     */
    public function getAllAuthors(): array
    {
        $qb = $this->createQueryBuilder('author')
            ->select('author.authors as item')
            ->addSelect('COUNT(author.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.finished) as booksFinished')
            ->leftJoin('author.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user=:user')
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
            ->leftJoin('tag.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.finished = true or bookInteraction.hidden=true) and bookInteraction.user=:user')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('tag.tags')
            ->getQuery();

        return $this->convertResults($qb->getResult());
    }

    /**
     * When we group by tags, for authors and tags, we get an array of arrays, so we need to convert it to an array of strings
     * @param UnconvertedGroupType[] $intermediateResults
     * @return GroupType[]
     */
    private function convertResults(mixed $intermediateResults): array
    {
        $results = [];
        foreach ($intermediateResults as $result) {
            if (!is_array($result['item'])) {
                $results[$result['item']] = [
                    'item' => $result['item'],
                    'bookCount' => $result['bookCount'],
                    'booksFinished' => $result['booksFinished'],
                ];
                continue;
            }
            foreach ($result['item'] as $item) {
                $key = ucwords(strtolower((string) $item), Book::UCWORDS_SEPARATORS);
                if (!array_key_exists($key, $results)) {
                    $results[$key] = [
                        'item' => $key,
                        'bookCount' => 0,
                        'booksFinished' => 0,
                    ];
                }
                $results[$key] = [
                    'item' => $key,
                    'bookCount' => $result['bookCount'] + $results[$key]['bookCount'],
                    'booksFinished' => $result['booksFinished'] + $results[$key]['booksFinished'],
                ];
            }
        }

        ksort($results);

        return $results;
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    /**
     * @return array<int, Book>
     */
    public function getChangedBooks(KoboDevice $koboDevice, SyncToken $syncToken, int $firstResult, int $maxResults): array
    {
        $qb = $this->getChangedBooksQueryBuilder($koboDevice, $syncToken);
        $qb->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        $qb->orderBy('book.updated', 'ASC');

        $query = $qb->getQuery();
        /** @var Book[] $result */
        $result = $query->getResult();

        return $result;
    }

    public function getChangedBooksCount(KoboDevice $koboDevice, SyncToken $syncToken): int
    {
        $qb = $this->getChangedBooksQueryBuilder($koboDevice, $syncToken);
        $qb->select('count(distinct book.id) as nb');
        $qb->resetDQLPart('groupBy');

        /** @var array{0: int} $result */
        $result = $qb->getQuery()->getSingleColumnResult();

        return $result[0];
    }

    private function getChangedBooksQueryBuilder(KoboDevice $koboDevice, SyncToken $syncToken): QueryBuilder
    {
        $books = [];
        foreach ($koboDevice->getShelves() as $shelf) {
            $shelfBooks = $this->shelfManager->getBooksInShelf($shelf);
            foreach ($shelfBooks as $book) {
                $books[$book->getId()] = $book;
            }
        }

        if ($koboDevice->isSyncReadingList()) {
            $readingList = $this->_em->getRepository(BookInteraction::class)->getFavourite();
            foreach ($readingList as $bookInteraction) {
                $book = $bookInteraction->getBook();
                if ($book === null) {
                    continue;
                }
                $books[$book->getId()] = $book;
            }
        }

        $qb = $this->createQueryBuilder('book')
            ->select('book', 'bookInteractions')
            ->leftJoin('book.bookInteractions', 'bookInteractions')
            ->leftJoin('book.koboSyncedBooks', 'koboSyncedBooks', 'WITH', 'koboSyncedBooks.koboDevice = :id')
            ->where('book.extension = :extension')
            ->andWhere('book in (:books)')
            ->setParameter('books', array_keys($books))
            ->setParameter('id', $koboDevice->getId())
            ->setParameter('extension', 'epub') // Pdf is not supported by kobo sync
            ->groupBy('book.id');
        $bigOr = $qb->expr()->orX();

        if ($syncToken->lastCreated instanceof \DateTimeInterface) {
            $bigOr->addMultiple([
                $qb->expr()->isNull('koboSyncedBooks.created'),
                $qb->expr()->gte('book.created', ':lastCreated'),
            ]);
            $qb->setParameter('lastCreated', $syncToken->lastCreated);
        }

        if ($syncToken->lastModified instanceof \DateTimeInterface) {
            $bigOr->addMultiple([
                'book.updated > :lastModified',
                'book.created  > :lastModified',
                'koboSyncedBooks.updated > :lastModified',
                $qb->expr()->isNull('koboSyncedBooks.updated'),
            ]);
            $qb->setParameter('lastModified', $syncToken->lastModified);
        }

        $qb->andWhere($bigOr);

        $qb->orderBy('book.updated');
        if ($syncToken->filters['PrioritizeRecentReads'] ?? false) {
            $qb->orderBy('bookInteractions.updated', 'ASC');
            $qb->addOrderBy('bookInteractions.finished', 'ASC');
        }

        return $qb;
    }

    public function findByIdAndKoboDevice(int $bookId, KoboDevice $koboDevice): ?Book
    {
        /** @var Book|null $result */
        $result = $this->createQueryBuilder('book')
            ->select('book')
            ->join('book.shelves', 'shelves')
            ->join('shelves.koboDevices', 'koboDevice')
            ->where('koboDevice.id = :koboId')
            ->andWhere('book.id = :bookId')
            ->setParameter('koboId', $koboDevice->getId())
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function findByUuidAndKoboDevice(string $bookUuid, KoboDevice $koboDevice): ?Book
    {
        /** @var Book|null $result */
        $result = $this->createQueryBuilder('book')
            ->select('book')
            ->join('book.shelves', 'shelves')
            ->join('shelves.koboDevices', 'koboDevice')
            ->where('koboDevice.id = :koboDeviceId')
            ->andWhere('book.uuid = :bookUuid')
            ->setParameter('koboDeviceId', $koboDevice->getId())
            ->setParameter('bookUuid', $bookUuid)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
