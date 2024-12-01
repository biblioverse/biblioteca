<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\BookmarkUser;
use App\Entity\KoboDevice;
use App\Kobo\SyncToken;
use App\Service\BookProgressionService;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ReadingStateResponse
{
    public function __construct(
        protected BookProgressionService $bookProgressionService,
        protected SerializerInterface $serializer,
        protected SyncToken $syncToken,
        protected KoboDevice $kobo,
        protected Book $book,
    ) {
    }

    /**
     * @return array<int,array<string, mixed>>
     */
    public function createReadingState(): array
    {
        $book = $this->book;
        $uuid = $book->getUuid();

        $lastModified = $this->syncToken->maxLastModified($this->kobo->getUser()->getBookmarkForBook($book)?->getUpdated(), $book->getUpdated(), $this->syncToken->currentDate, $book->getLastInteraction($this->kobo->getUser())?->getUpdated());

        return [[
            'EntitlementId' => $uuid,
            'Created' => $this->syncToken->maxLastCreated($book->getCreated(), $this->syncToken->currentDate, $book->getLastInteraction($this->kobo->getUser())?->getCreated()),
            'LastModified' => $lastModified,
            'PriorityTimestamp' => $lastModified,
            'StatusInfo' => [
                'LastModified' => $lastModified,
                'Status' => match ($this->isReadingFinished($book)) {
                    true => SyncResponse::READING_STATUS_FINISHED,
                    false => SyncResponse::READING_STATUS_IN_PROGRESS,
                    null => SyncResponse::READING_STATUS_UNREAD,
                },
                'TimesStartedReading' => 0,
            ],

            // "Statistics"=> get_statistics_response(kobo_reading_state.statistics),
            'CurrentBookmark' => $this->createBookmark($this->kobo->getUser()->getBookmarkForBook($book)),
        ]];
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

        return $values; // Remove null values
    }

    public function __toString(): string
    {
        return $this->serializer->serialize($this->createReadingState(), 'json', [DateTimeNormalizer::FORMAT_KEY => SyncResponse::DATE_FORMAT]);
    }
}
