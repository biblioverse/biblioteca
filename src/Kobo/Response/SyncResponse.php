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
    public const DATE_FORMAT = \DateTimeInterface::RFC3339; // "Y-m-d\TH:i:s\Z";

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
        $data = [
            'BookEntitlement' => $this->getEntitlements(),
            'BookMetadata' => $this->getMetaData(),
            'ReadingState' => $this->getReadingStates(),
            'NewEntitlement' => $this->getNewEntitlement(),
            'ChangedEntitlement' => $this->getChangedEntitlement(),
            'ChangedReadingState' => [
                'ReadingState' => $this->getChangedReadingState(),
            ],
            'NewTags' => $this->getNewTags(),
        ];

        $response = new JsonResponse();
        $response->setContent($this->serializer->serialize($data, 'json', [DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]));

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
     * @return array<int, BookEntitlement>
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

        return array_map(fn (Book $book) => $this->createEntitlement($book), $books);
    }

    /**
     * @return array<int, BookReadingState>
     */
    private function getChangedReadingState(): array
    {
        if ($this->syncToken->readingStateLastModified === null) {
            return [];
        }

        $response = [];
        foreach ($this->books as $book) {
            foreach ($book->getBookInteractions() as $interaction) {
                if ($interaction->getUpdated() !== null && $interaction->getUpdated() <= $this->syncToken->readingStateLastModified) {
                    continue;
                }
                $response[] = $this->createReadingState($book);
            }
        }

        return $response;
    }

    /**
     * @return array<int, BookEntitlement>
     */
    private function getNewEntitlement(): array
    {
        $books = array_filter($this->books, function (Book $book) {
            // This book has never been synced before
            return $book->getKoboSyncedBook()->isEmpty();
        });

        return array_map(fn (Book $book) => $this->createEntitlement($book), $books);
    }

    /**
     * @return array<int, BookEntitlement>
     */
    private function getEntitlements(): array
    {
        return [];
    }

    /**
     * @return array<int, BookMetadata>
     */
    private function getMetaData(): array
    {
        return [];
    }

    /**
     * @return array<int, BookReadingState>
     */
    private function getReadingStates(): array
    {
        return [];
    }

    /**
     * New tags are newly created shelves
     * @return BookTag[]
     */
    private function getNewTags(): array
    {
        $response = [];
        foreach ($this->shelves as $shelf) {
            $response[] = $this->createBookTagFromShelf($shelf);
        }

        return $response;
    }

    /**
     * @param Shelf $shelf
     * @return BookTag
     */
    public function createBookTagFromShelf(Shelf $shelf): array
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
}
