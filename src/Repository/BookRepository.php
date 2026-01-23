<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\User;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use App\Kobo\SyncToken\SyncTokenInterface;
use App\Service\ShelfManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @phpstan-type UnconvertedGroupType array{ item:null|string|array, bookCount:int, booksFinished:int }
 * @phpstan-type GroupType array{ item:string|null, bookCount:int, booksFinished:int }
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
        private readonly ShelfManager $shelfManager,
        private readonly LoggerInterface $koboSyncLogger,
    ) {
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
            ->where('bookInteraction.readStatus = :readStatus')
            ->setParameter('readStatus', ReadStatus::Finished)
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
            ->where('bookInteraction.readStatus = :read_status')
            ->setParameter('read_status', ReadStatus::Finished)
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

            $qb->leftJoin('book.bookInteractions', 'bookInteractions');
            $qb->addSelect('bookInteractions');

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

        // @phpstan-ignore-next-line
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

        $qb->leftJoin('book.bookInteractions', 'bookInteractions');
        $qb->addSelect('bookInteractions');

        // @phpstan-ignore-next-line
        return $qb->getQuery()->getResult();
    }

    /***
     * @param string $serie
     * @return Book[]
     */
    public function findBySerie(string $serie): array
    {
        $qb = $this->getAllBooksQueryBuilder();

        $qb->andWhere('book.serie=:serie');
        $qb->setParameter('serie', $serie);

        $qb->orderBy('book.serieIndex', 'ASC');

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $qb->andWhere('COALESCE(book.ageCategory,1) <= COALESCE(:ageCategory,10)');
            $qb->setParameter('ageCategory', $user->getMaxAgeCategory());
        }

        $qb->leftJoin('book.bookInteractions', 'bookInteractions');
        $qb->addSelect('bookInteractions');
        $qb->leftJoin('book.shelves', 'shelves');
        $qb->addSelect('shelves');

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    public function getFirstUnreadBook(string $serie): Book
    {
        $books = $this->findBySerie($serie);
        if ($books === []) {
            throw new NotFoundHttpException('No books found for this serie');
        }
        $firstUnreadBook = null;
        foreach ($books as $book) {
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new \RuntimeException('Invalid user');
            }
            $li = $book->getLastInteraction($user);
            if ($li === null || !$li->isFinished()) {
                $firstUnreadBook = $book;
                break;
            }
        }

        if ($firstUnreadBook === null) {
            $firstUnreadBook = $books[0];
        }

        return $firstUnreadBook;
    }

    /**
     * @return GroupType[]
     */
    public function getAllSeries(): array
    {
        $result = $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished')
            ->setParameter('finished', ReadStatus::Finished)
            ->where('serie.serie IS NOT NULL')
            ->leftJoin('serie.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.readStatus = :finished or bookInteraction.readingList=:ignored) and bookInteraction.user= :user')
            ->setParameter('ignored', ReadingList::Ignored)
            ->setParameter('user', $this->security->getUser())
            ->addGroupBy('serie.serie')->getQuery();

        // @phpstan-ignore-next-line
        return $this->convertResults($result->getResult());
    }

    public function getIncompleteSeries(): Query
    {
        $qb = $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished')
            ->setParameter('finished', ReadStatus::Finished);

        $qb = $this->joinInteractions($qb, 'serie');

        return $qb->where('serie.serie IS NOT NULL')
            ->addGroupBy('serie.serie')
            ->having('count(serie.id) != max(serie.serieIndex)')
            ->getQuery();
    }

    public function getStartedSeries(int $limit = 100): Query
    {
        $qb = $this->createQueryBuilder('serie')
            ->select('serie.serie as item')
            ->addSelect('COUNT(serie.id) as bookCount')
            ->addSelect('MAX(serie.serieIndex) as lastBookIndex')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished')
            ->addSelect('MAX(bookInteraction.finishedDate) as dateFinished')
            ->where('serie.serie IS NOT NULL')
            ->orderBy('MAX(bookInteraction.finishedDate)', 'DESC')
            ->setMaxResults($limit);
        $qb = $this->joinInteractions($qb, 'serie');

        return $qb->addGroupBy('serie.serie')
            ->having('COUNT(bookInteraction.readStatus)>0 AND COUNT(bookInteraction.readStatus)<MAX(serie.serieIndex)')
            ->getQuery();
    }

    /**
     * @return GroupType[]
     */
    public function getAllPublishers(): array
    {
        $qb = $this->createQueryBuilder('publisher')
            ->select('publisher.publisher as item')
            ->addSelect('COUNT(publisher.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished')
            ->where('publisher.publisher IS NOT NULL');
        $qb = $this->joinInteractions($qb, 'publisher');

        $results = $qb->addGroupBy('publisher.publisher')->getQuery();

        // @phpstan-ignore-next-line
        return $this->convertResults($results->getResult());
    }

    private function joinInteractions(QueryBuilder $qb, string $alias = 'book'): QueryBuilder
    {
        return $qb->leftJoin($alias.'.bookInteractions', 'bookInteraction', 'WITH', '(bookInteraction.readStatus = :status_finished or 
        bookInteraction.readingList=:list_ignored) and bookInteraction.user= :user')
            ->setParameter('status_finished', ReadStatus::Finished)
            ->setParameter('list_ignored', ReadingList::Ignored)
            ->setParameter('user', $this->security->getUser());
    }

    /**
     * @return GroupType[]
     */
    public function getAllAuthors(): array
    {
        $qb = $this->createQueryBuilder('author')
            ->select('author.authors as item')
            ->addSelect('COUNT(author.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished');
        $qb = $this->joinInteractions($qb, 'author');

        $qb->addGroupBy('author.authors');

        // @phpstan-ignore-next-line
        return $this->convertResults($qb->getQuery()->getResult());
    }

    /**
     * @return GroupType[]
     */
    public function getAllTags(): array
    {
        $qb = $this->createQueryBuilder('tag')
            ->select('tag.tags as item')
            ->addSelect('COUNT(tag.id) as bookCount')
            ->addSelect('COUNT(bookInteraction.readStatus) as booksFinished');
        $qb = $this->joinInteractions($qb, 'tag');

        $qb->addGroupBy('tag.tags');

        // @phpstan-ignore-next-line
        return $this->convertResults($qb->getQuery()->getResult());
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
                if ($result['item'] === null) {
                    continue;
                }
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
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<int, Book>
     */
    public function getChangedBooks(KoboDevice $koboDevice, SyncTokenInterface $syncToken, int $firstResult, int $maxResults): array
    {
        $qb = $this->getChangedBooksQueryBuilder($koboDevice, $syncToken);
        $qb->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        $qb->orderBy('book.updated', 'ASC');

        $query = $qb->getQuery();
        /** @var Book[] $result */
        $result = $query->getResult();
        $sql = $query->getSQL();
        $params = $query->getParameters();
        $this->koboSyncLogger->debug('Query: {query}', ['query' => $sql, 'params' => $params, 'result' => $result]);

        return $result;
    }

    public function getChangedBooksCount(KoboDevice $koboDevice, SyncTokenInterface $syncToken): int
    {
        $qb = $this->getChangedBooksQueryBuilder($koboDevice, $syncToken);
        $qb->select('count(distinct book.id) as nb');
        $qb->resetDQLPart('groupBy');

        /** @var array{0: int} $result */
        $result = $qb->getQuery()->getSingleColumnResult();

        return $result[0];
    }

    private function getChangedBooksQueryBuilder(KoboDevice $koboDevice, SyncTokenInterface $syncToken): QueryBuilder
    {
        $qb = $this->createQueryBuilder('book')
            ->select('book', 'bookInteractions')
            ->leftJoin('book.bookInteractions', 'bookInteractions')
            ->leftJoin('book.koboSyncedBooks', 'koboSyncedBooks', 'WITH', 'koboSyncedBooks.koboDevice = :koboDeviceId')
            ->leftJoin('book.shelves', 'shelves')
            ->leftJoin('shelves.koboDevices', 'koboDeviceShelf')
            ->where('book.extension = :extension')
            ->setParameter('koboDeviceId', $koboDevice->getId())
            ->setParameter('extension', 'epub') // Pdf is not supported by kobo sync
            ->groupBy('book.id');

        // We sync
        // - Book already synced with the bigOr Condition
        // - Book never synced but in a shelf, reading list or dynamic shelf
        // We start by building the bigOr condition
        $bigOr = $qb->expr()->orX();
        if ($syncToken->getLastCreated() instanceof \DateTimeInterface) {
            $bigOr->addMultiple([
                $qb->expr()->isNull('koboSyncedBooks.created'),
                $qb->expr()->gte('book.created', ':lastCreated'),
                $qb->expr()->gte('bookInteractions.created', ':lastCreated'),
            ]);
            $qb->setParameter('lastCreated', $syncToken->getLastCreated());
        }

        if ($syncToken->getArchiveLastModified() instanceof \DateTimeInterface) {
            $bigOr->add(
                $qb->expr()->andX(
                    $qb->expr()->gte('koboSyncedBooks.archived', ':archiveLastModified'),
                    $qb->expr()->isNotNull('koboSyncedBooks.archived'),
                ),
            );
            $qb->setParameter('archiveLastModified', $syncToken->getArchiveLastModified());
        }

        if ($syncToken->getLastModified() instanceof \DateTimeInterface) {
            $bigOr->addMultiple([
                'book.updated > :lastModified',
                'book.created  > :lastModified',
                'koboSyncedBooks.updated > :lastModified',
                'bookInteractions.updated > :lastModified',
                $qb->expr()->isNull('koboSyncedBooks.updated'),
            ]);
            $qb->setParameter('lastModified', $syncToken->getLastModified());
        }

        $qb->orderBy('book.updated');
        if ($syncToken->getFilters()['PrioritizeRecentReads'] ?? false) {
            $qb->orderBy('bookInteractions.updated', 'ASC');
            $qb->addOrderBy('bookInteractions.readStatus', 'ASC');
        }

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX($qb->expr()->isNotNull('koboSyncedBooks.koboDevice'), $bigOr),
                $qb->expr()->andX($qb->expr()->isNull('koboSyncedBooks.koboDevice'), $this->getExpressionInShelf($koboDevice, $qb)),
            )
        );

        return $qb;
    }

    /**
     * Expression to fetch only the books from a shelf, reading list or dynamic shelf
     */
    private function getExpressionInShelf(KoboDevice $koboDevice, QueryBuilder $qb): Orx
    {
        $expressionInShelf = $qb->expr()->orX();

        // In a synced shelf
        $expressionInShelf->add($qb->expr()->eq('koboDeviceShelf.id', ':koboDeviceId2'));
        $qb->setParameter('koboDeviceId2', $koboDevice->getId());
        // In a dynamic shelf
        $expressionInShelf->add($qb->expr()->in('book.id', ':dynamicBooksIds'));
        $qb->setParameter('dynamicBooksIds', $this->getDynamicBooksIdsForKoboDevice($koboDevice));

        // In the reading list
        if ($koboDevice->isSyncReadingList()) {
            $expressionInShelf->add($qb->expr()->andX(
                $qb->expr()->eq('bookInteractions.readingList', ':to_read'),
                $qb->expr()->neq('bookInteractions.readStatus', ':finished'),
                $qb->expr()->eq('bookInteractions.user', ':userInteraction'),
            ));
            $qb->setParameter('to_read', ReadingList::ToRead);
            $qb->setParameter('userInteraction', $koboDevice->getUser());
            $qb->setParameter('finished', ReadStatus::Finished);
        }

        return $expressionInShelf;
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

    /**
     * @deprecated use findByUuid instead
     */
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

    public function inHowManyStaticKoboShelves(Book $book, ?User $user): int
    {
        $qb = $this->createQueryBuilder('book')
            ->select('count(shelves.id)')
            ->join('book.shelves', 'shelves')
            ->join('shelves.koboDevices', 'koboDevice')
            ->where('koboDevice.user = :user')
            ->andWhere('book.id = :bookId')
            ->setParameter('user', $user)
            ->setParameter('bookId', $book->getId())
        ;

        /** @var array{0: int} $result */
        $result = $qb->getQuery()->getSingleColumnResult();

        return $result[0];
    }

    /**
     * @return int[]
     */
    private function getDynamicBooksIdsForKoboDevice(KoboDevice $koboDevice): array
    {
        $dynamicBooksIds = [];
        foreach ($koboDevice->getShelves() as $shelf) {
            if (!$shelf->isDynamic()) {
                continue;
            }

            // TODO: Use multi-search (when ready) to avoid many queries
            $shelfBooks = $this->shelfManager->getBooksInShelf($shelf);
            foreach ($shelfBooks as $book) {
                $dynamicBooksIds[] = $book->getId();
            }
        }

        return $dynamicBooksIds;
    }

    public function deleteByTitle(string $title): int
    {
        /** @var int $result */
        $result = $this->createQueryBuilder('b')
            ->delete()
            ->where('b.title = :title')
            ->setParameter('title', $title)
            ->getQuery()->execute();

        return $result;
    }
}
