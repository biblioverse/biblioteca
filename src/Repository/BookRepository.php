<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Kobo;
use App\Kobo\SyncToken;
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

        $results = $qb->getQuery()->getResult();
        if (!is_array($results)) {
            return [];
        }

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
        $qb = $this->getAllBooksQueryBuilder();

        $orModule = $qb->expr()->orX();

        foreach ($book->getAuthors() as $key => $author) {
            $orModule->add('JSON_CONTAINS(lower(book.authors), :author'.$key.')=1');
            $qb->setParameter('author'.$key, json_encode([strtolower($author)]));
        }
        $qb->andWhere($orModule);

        $results = $qb->getQuery()->getResult();

        if (!is_array($results)) {
            return [];
        }

        $items = array_filter($results, static fn ($result) => $result->getId() !== $book->getId() && ($result->getSerie() === null || $book->getSerie() !== $result->getSerie()));

        if (count($items) === 0) {
            return [];
        }

        $randed = array_rand($items, min(count($items), $maxResults));
        if (!is_array($randed)) {
            $randed = [$randed];
        }

        return array_filter($items, static fn ($key) => in_array($key, $randed, true), ARRAY_FILTER_USE_KEY);
    }

    public function findByAuthor(string $author): mixed
    {
        $qb = $this->getAllBooksQueryBuilder();

        $orModule = $qb->expr()->orX();

        $orModule->add('JSON_CONTAINS(lower(book.authors), :author)=1');
        $qb->setParameter('author', json_encode([strtolower($author)]));

        $qb->andWhere($orModule);

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
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
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
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', 'bookInteraction.finished = true and bookInteraction.user= :user')
            ->where('serie.serie IS NOT NULL')
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')
            ->having('count(serie.id) != max(serie.serieIndex)')
            ->getQuery();
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
                $key = ucwords(strtolower($item), Book::UCWORDS_SEPARATORS);
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
     * @param Kobo $kobo
     * @param SyncToken $syncToken
     * @return array<int, Book>
     */
    public function getChangedBooks(Kobo $kobo, SyncToken $syncToken, int $firstResult, int $maxResults): array
    {
        $qb = $this->getChangedBooksQueryBuilder($kobo, $syncToken);
        $qb->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        $qb->orderBy('book.updated', 'ASC');
        /** @var Book[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function getChangedBooksCount(Kobo $kobo, SyncToken $syncToken): int
    {
        $qb = $this->getChangedBooksQueryBuilder($kobo, $syncToken);
        $qb->select('count(book.id) as nb');

        return (int) $qb->getQuery()->getSingleColumnResult();
    }

    private function getChangedBooksQueryBuilder(Kobo $kobo, SyncToken $syncToken): QueryBuilder
    {
        $qb = $this->createQueryBuilder('book')
            ->select('book', 'koboSyncedBooks', 'bookInteractions')
            ->join('book.shelves', 'shelves')
            ->join('shelves.kobos', 'kobo')
            ->leftJoin('book.bookInteractions', 'bookInteractions')
            ->leftJoin('book.koboSyncedBooks', 'koboSyncedBooks', 'WITH', 'koboSyncedBooks.kobo = :kobo')
            ->where('kobo.id = :id')
            ->andWhere('book.extension = :extension')
            ->setParameter('id', $kobo->getId())
            ->setParameter('kobo', $kobo)
            ->setParameter('extension', 'epub'); // Pdf is not supported by kobo sync

        if ($syncToken->lastCreated !== null) {
            $qb->andWhere('book.created > :lastCreated');
            $qb->orWhere($qb->expr()->orX(
                $qb->expr()->isNull('koboSyncedBooks.created is null'),
                $qb->expr()->isNull('koboSyncedBooks.created > :lastCreated'),
            ))
            ->setParameter('lastCreated', $syncToken->lastCreated);
        }

        if ($syncToken->lastModified !== null) {
            $qb->andWhere($qb->expr()->orX(
                'book.updated > :lastModified',
                'book.created  > :lastModified',
                'koboSyncedBooks.updated > :lastModified',
                $qb->expr()->isNull('koboSyncedBooks.updated'),
            ));
            $qb->setParameter('lastModified', $syncToken->lastModified);
        }

        $qb->orderBy('book.updated');
        if ($syncToken->filters['PrioritizeRecentReads'] ?? false) {
            $qb->orderBy('bookInteractions.updated', 'ASC');
            $qb->addOrderBy('bookInteractions.finished', 'ASC');
        }

        return $qb;
    }

    public function findByIdAndKobo(int $bookId, Kobo $kobo): ?Book
    {
        /** @var Book|null $result */
        $result = $this->createQueryBuilder('book')
            ->select('book')
            ->join('book.shelves', 'shelves')
            ->join('shelves.kobos', 'kobo')
            ->where('kobo.id = :koboId')
            ->andWhere('book.id = :bookId')
            ->setParameter('koboId', $kobo->getId())
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function findByUuidAndKobo(string $bookUuid, Kobo $kobo): ?Book
    {
        /** @var Book|null $result */
        $result = $this->createQueryBuilder('book')
            ->select('book')
            ->join('book.shelves', 'shelves')
            ->join('shelves.kobos', 'kobo')
            ->where('kobo.id = :koboId')
            ->andWhere('book.uuid = :bookUuid')
            ->setParameter('koboId', $kobo->getId())
            ->setParameter('bookUuid', $bookUuid)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
