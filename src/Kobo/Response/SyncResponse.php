<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\BookmarkUser;
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

    public function __construct(
        protected MetadataResponseService $metadataResponse,
        protected BookProgressionService $bookProgressionService,
        protected SyncToken $syncToken,
        protected KoboDevice $kobo,
        protected SerializerInterface $serializer)
    {
    }

    public function toJsonResponse(): JsonResponse
    {
        $list = [];
        array_push($list, ...$this->getNewEntitlement());
        array_push($list, ...$this->getChangedEntitlement());
        array_push($list, ...$this->getNewTags());
        array_push($list, ...$this->getChangedTag());
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
    private function createReadingState(Book $book): array
    {
        $uuid = $book->getUuid();

        return [
            'EntitlementId' => $uuid,
            'Created' => $this->syncToken->maxLastCreated($book->getCreated(), $this->syncToken->currentDate),
            'LastModified' => $this->syncToken->maxLastModified($book->getUpdated(), $this->syncToken->currentDate),

            // PriorityTimestamp is always equal to LastModified.
            'PriorityTimestamp' => $this->syncToken->maxLastCreated($book->getCreated(), $this->syncToken->currentDate),

            'StatusInfo' => [
                'LastModified' => $this->syncToken->maxLastModified($book->getLastInteraction($this->kobo->getUser())?->getUpdated(), $this->getLastBookmarkDate($book), $this->syncToken->currentDate),
                'Status' => match ($this->isReadingFinished($book)) {
                    true => SyncResponse::READING_STATUS_FINISHED,
                    false => SyncResponse::READING_STATUS_IN_PROGRESS,
                    null => SyncResponse::READING_STATUS_UNREAD,
                },
                'TimesStartedReading' => 0,
            ],

            // "Statistics"=> get_statistics_response(kobo_reading_state.statistics),
            'CurrentBookmark' => $this->createBookmark($this->kobo->getUser()->getBookmarkForBook($book)),
        ];
    }

    /**
     * @return bool|null Null if we do not now the reading state
     */
    private function isReadingFinished(Book $book): ?bool
    {
        $progression = $this->bookProgressionService->getProgression($book, $this->kobo->getUser());
        if ($progression === null) {
            return null;
        }

        return $progression >= 1.0;
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

            $lastInteraction = $book->getLastInteraction($this->kobo->getUser());

            return $book->getUpdated() >= $this->syncToken->lastModified
                || !$book->getUpdated() instanceof \DateTimeInterface
                || $book->getCreated() >= $this->syncToken->lastCreated
                || ($lastInteraction instanceof BookInteraction && $lastInteraction->getUpdated() >= $this->syncToken->lastModified);
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
            return $shelf->getCreated() < $this->syncToken->lastCreated;
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
        return [
            'BookEntitlement' => $this->createEntitlement($book),
            'BookMetadata' => $this->metadataResponse->fromBook($book, $this->kobo, $this->syncToken),
            'ReadingState' => $this->createReadingState($book),
        ];
    }

    private function createBookmark(?BookmarkUser $bookMark): array
    {
        if (!$bookMark instanceof BookmarkUser) {
            return [];
        }

        $values = [
            'Location' => [
                'Type' => $bookMark->getLocationType(),
                'Value' => $bookMark->getLocationValue(),
                'Source' => $bookMark->getLocationSource(),
            ],
            'ProgressPercent' => $bookMark->getPercentAsInt(),
            'ContentSourceProgressPercent' => $bookMark->getSourcePercentAsInt(),
        ];

        if (false === $bookMark->hasLocation()) {
            unset($values['Location']);
        }

        return array_filter($values); // Remove null values
    }

    private function getLastBookmarkDate(Book $book): ?\DateTimeInterface
    {
        return $this->kobo->getUser()->getBookmarkForBook($book)?->getUpdated();
    }
}
