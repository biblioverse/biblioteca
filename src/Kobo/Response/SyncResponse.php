<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\Kobo;
use App\Entity\Shelf;
use App\Kobo\SyncToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

// Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py

/**
 * @phpstan-type BookEntitlement array<string, mixed>
 * @phpstan-type BookMetadata array<string, mixed>
 * @phpstan-type BookReadingState array<string, mixed>
 * @phpstan-type BookTag array<string, mixed>
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

    public function __construct(protected MetadataResponseService $metadataResponse, protected SyncToken $syncToken, protected Kobo $kobo, protected SerializerInterface $serializer)
    {
    }

    public function toJsonResponse(): JsonResponse
    {
        $list = [];
        array_push($list, ...$this->getNewEntitlement());
        array_push($list, ...$this->getChangedEntitlement());
        array_push($list, ...$this->getNewTags());
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
     * @param Book $book
     * @param bool $removed If the book was removed from the library ?
     * @return BookEntitlement
     */
    private function createEntitlement(Book $book, bool $removed = false): array
    {
        $uuid = $book->getUuid();

        return [
            'Accessibility' => 'Full',
            'ActivePeriod' => ['From' => $book->getCreated()],
            'Created' => $book->getCreated(),
            'CrossRevisionId' => $uuid,
            'Id' => $uuid,
            'IsRemoved' => $removed,
            'IsHiddenFromArchive' => false,
            'IsLocked' => false,
            'LastModified' => $book->getUpdated(),
            'OriginCategory' => 'Imported',
            'RevisionId' => $uuid,
            'Status' => 'Active',
        ];
    }

    /**
     * @param Book $book
     * @return BookReadingState
     */
    private function createReadingState(Book $book): array
    {
        $uuid = $book->getUuid();

        return [
            'EntitlementId' => $uuid,
            'Created' => $book->getUpdated(),
            'LastModified' => $book->getUpdated(),
            // PriorityTimestamp is always equal to LastModified.
            'PriorityTimestamp' => $book->getUpdated(),
            'StatusInfo' => [
                'LastModified' => $book->getUpdated(),
                'Status' => match ($this->isReadingFinished($book)) {
                    true => SyncResponse::READING_STATUS_FINISHED,
                    false => SyncResponse::READING_STATUS_IN_PROGRESS,
                    null => SyncResponse::READING_STATUS_UNREAD,
                },
            ],

            // "Statistics"=> get_statistics_response(kobo_reading_state.statistics),
            // "CurrentBookmark"=> get_current_bookmark_response(kobo_reading_state.current_bookmark),
        ];
    }

    private function isReadingFinished(Book $book): ?bool
    {
        // TODO Rad book interaction to know it.
        foreach ($book->getBookInteractions() as $interaction) {
            if ($interaction->isFinished()) {
                return true;
            }

            return false;
        }

        return null;
    }

    /**
     * @return array<int, object>
     */
    private function getChangedEntitlement(): array
    {
        $books = array_filter($this->books, function (Book $book) {
            // This book has not been synced before, so it's a NewEntitlement
            if ($book->getKoboSyncedBook()->isEmpty()) {
                return false;
            }

            return $book->getUpdated() >= $this->syncToken->lastModified || $book->getUpdated() === null || $book->getCreated() >= $this->syncToken->lastCreated;
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
            return $book->getKoboSyncedBook()->isEmpty();
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
        return array_map(function (Shelf $shelf) {
            $response = new \stdClass();
            $response->NewTag = $this->createBookTagFromShelf($shelf);

            return $response;
        }, $this->shelves);
    }

    /**
     * @param Shelf $shelf
     * @return BookTag
     */
    private function createBookTagFromShelf(Shelf $shelf): array
    {
        return [
            'Tag' => [
                'Created' => $shelf->getCreated(),
                'Id' => $shelf->getUuid(),
                'Items' => [
                    [
                        'RevisionId' => $shelf->getUuid(),
                        'Type' => 'ProductRevisionTagItem',
                    ],
                ],
                'LastModified' => $shelf->getUpdated(),
                'Name' => $shelf->getName(),
                'Type' => 'UserTag',
            ],
        ];
    }

    private function createBookEntitlement(Book $book): array
    {
        return [
            'BookEntitlement' => $this->createEntitlement($book),
            'BookMetadata' => $this->metadataResponse->fromBook($book, $this->kobo, $this->syncToken),
            'ReadingState' => $this->createReadingState($book),
        ];
    }
}
