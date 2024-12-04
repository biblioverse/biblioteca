<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Kobo\SyncToken;
use App\Service\BookProgressionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

// Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py

/**
 * @phpstan-type BookEntitlement array<string, mixed>
 * @phpstan-type BookMetadata array<string, mixed>
 * @phpstan-type BookReadingState array<int,array<string, mixed>>
 * @phpstan-type BookTag array<string, mixed>
 * @phpstan-type RemoteItem array<int, object>
 * @phpstan-type RemoteItems array<int, RemoteItem>
 */
class SyncResponse
{
    public const DATE_FORMAT = "Y-m-d\TH:i:s\Z";

    /** @var array<int, Book> */
    private array $books = [];
    /** @var Shelf[] */
    private array $shelves = [];

    public const READING_STATUS_UNREAD = 'ReadyToRead';
    public const READING_STATUS_FINISHED = 'Finished';
    public const READING_STATUS_IN_PROGRESS = 'Reading';
    private SyncResponseHelper $helper;

    /**
     * @var RemoteItems
     */
    private array $remoteItems = [];

    public function __construct(
        protected MetadataResponseService $metadataResponse,
        protected BookProgressionService $bookProgressionService,
        protected SyncToken $syncToken,
        protected KoboDevice $koboDevice,
        protected SerializerInterface $serializer,
        protected ReadingStateResponseFactory $readingStateResponseFactory,
    ) {
        $this->helper = new SyncResponseHelper();
    }

    public function toJsonResponse(): JsonResponse
    {
        $list = [];
        array_push($list, ...$this->getNewEntitlement());
        array_push($list, ...$this->getChangedEntitlement());
        array_push($list, ...$this->getChangedReadingState());
        array_push($list, ...$this->getNewTags());
        array_push($list, ...$this->getChangedTag());

        $list = array_merge($list, $this->remoteItems);

        array_filter($list);

        $response = new JsonResponse();
        $response->setContent($this->serializer->serialize($list, 'json', [DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]));

        return $response;
    }

    protected function addBook(Book $book): void
    {
        $this->books[] = $book;
    }

    /**
     * @param Shelf[] $shelves
     * @return $this
     */
    public function addShelves(array $shelves): self
    {
        $this->shelves = $shelves;

        return $this;
    }

    /**
     * @param array<int,Book> $books
     * @return $this
     */
    public function addBooks(array $books): self
    {
        foreach ($books as $book) {
            $this->addBook($book);
        }

        return $this;
    }

    /**
     * @param bool $removed If the book was removed from the library ?
     * @return BookEntitlement
     */
    private function createEntitlement(Book $book, bool $removed = false): array
    {
        $uuid = $book->getUuid();

        return [
            'Accessibility' => 'Full',
            'ActivePeriod' => ['From' => $this->syncToken->maxLastModified($book->getUpdated(), $this->syncToken->currentDate)],
            'Created' => $this->syncToken->maxLastCreated($book->getCreated(), $this->syncToken->currentDate),
            'CrossRevisionId' => $uuid,
            'Id' => $uuid,
            'IsRemoved' => $removed,
            'IsHiddenFromArchive' => false,
            'IsLocked' => false,
            'LastModified' => $this->syncToken->maxLastModified($book->getUpdated(), $this->syncToken->currentDate),
            'OriginCategory' => 'Imported',
            'RevisionId' => $uuid,
            'Status' => 'Active',
        ];
    }

    /**
     * @return BookReadingState
     */
    public function createReadingState(Book $book): array
    {
        return $this->readingStateResponseFactory->create($this->syncToken, $this->koboDevice, $book)
            ->createReadingState();
    }

    /**
     * @return array<int, object>
     */
    private function getChangedEntitlement(): array
    {
        $books = array_filter($this->books, function (Book $book) {
            return $this->helper->isChangedEntitlement($book, $this->koboDevice, $this->syncToken);
        });

        return array_map(function (Book $book) {
            $response = new \stdClass();
            $response->ChangedEntitlement = $this->createBookEntitlement($book);

            return $response;
        }, $books);
    }

    /**
     * @return array<int, object>
     */
    private function getNewEntitlement(): array
    {
        $books = array_filter($this->books, function (Book $book) {
            // This book has never been synced before
            return $this->helper->isNewEntitlement($book, $this->syncToken);
        });

        return array_map(function (Book $book) {
            $response = new \stdClass();
            $response->NewEntitlement = $this->createBookEntitlement($book);

            return $response;
        }, $books);
    }

    /**
     * New tags are newly created shelves
     * @return array<int, object>
     */
    private function getNewTags(): array
    {
        $shelves = array_filter($this->shelves, function (Shelf $shelf) {
            return $shelf->getCreated() >= $this->syncToken->lastCreated;
        });

        return array_map(function (Shelf $shelf) {
            $response = new \stdClass();
            $response->NewTag = $this->createBookTagFromShelf($shelf);

            return $response;
        }, $shelves);
    }

    /**
     * New tags are newly created shelves
     * @return array<int, object>
     */
    private function getChangedTag(): array
    {
        $shelves = array_filter($this->shelves, function (Shelf $shelf) {
            return $this->syncToken->lastModified instanceof \DateTimeInterface && $shelf->getUpdated() < $this->syncToken->lastModified;
        });

        return array_map(function (Shelf $shelf) {
            $response = new \stdClass();
            $response->ChangedTag = $this->createBookTagFromShelf($shelf);

            return $response;
        }, $shelves);
    }

    /**
     * @return BookTag
     */
    private function createBookTagFromShelf(Shelf $shelf): array
    {
        return [
            'Tag' => [
                'Created' => $this->syncToken->maxLastCreated($shelf->getCreated()),
                'Id' => $shelf->getUuid(),
                'Items' => array_map(function (Book $book) {
                    return [
                        'RevisionId' => $book->getUuid(),
                        'Type' => 'ProductRevisionTagItem',
                    ];
                }, $this->books),
                'LastModified' => $this->syncToken->maxLastModified($shelf->getUpdated()),
                'Name' => $shelf->getName(),
                'Type' => 'UserTag',
            ],
        ];
    }

    private function createBookEntitlement(Book $book): array
    {
        $rs = $this->createReadingState($book);
        $rs = reset($rs);

        return [
            'BookEntitlement' => $this->createEntitlement($book),
            'BookMetadata' => $this->metadataResponse->fromBook($book, $this->koboDevice, $this->syncToken),
            'ReadingState' => $rs,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function getChangedReadingState(): array
    {
        $books = array_filter($this->books, function (Book $book) {
            return $this->helper->isChangedReadingState($book, $this->koboDevice, $this->syncToken);
        });

        return array_map(function (Book $book) {
            $response = new \stdClass();
            $rs = $this->createReadingState($book);
            $rs = reset($rs);
            $response->ChangedReadingState = $rs;

            return $response;
        }, $books);
    }

    /**
     * @param RemoteItems $items
     */
    public function addRemoteItems(array $items): self
    {
        $this->remoteItems = array_merge($this->remoteItems, $items);

        return $this;
    }
}
