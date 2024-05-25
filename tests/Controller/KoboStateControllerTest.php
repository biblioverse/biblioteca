<?php

namespace App\Tests\Controller;

use App\DataFixtures\BookFixture;
use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingState;
use App\Kobo\Request\ReadingStateLocation;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatistics;
use App\Kobo\Request\ReadingStateStatusInfo;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @phpstan-type ReadingStateCriteria array{'book':int, 'readPages': int, 'finished': boolean}
 */
class KoboStateControllerTest extends AbstractKoboControllerTest
{
    public function testOpen() : void
    {
        $client = static::getClient();
        $client?->setServerParameter('HTTP_CONNECTION', 'keep-alive');

        $book = $this->getBookById(BookFixture::ID);
        self::assertNotNull($book, 'Book '.BookFixture::ID.' not found');

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/'.$book->getUuid().'/state');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Connection', 'keep-alive');
    }

    /**
     * @dataProvider readingStatesProvider
     * @param ReadingStateCriteria $criteria
     */
    public function testPutState(int $bookId, ReadingStates $readingStates, array $criteria) : void
    {
        $client = static::getClient();
        $serializer = $this->getSerializer();

        $book = $this->getBookById($bookId);
        self::assertNotNull($book, 'Book '.$bookId.' not found');
        self::assertNotNull($book->getUuid(), 'Book '.$bookId.' has no UUID');

        $json = $serializer->serialize($readingStates, 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', $this->accessKey, $book->getUuid()), [],[],[] , $json);

        self::assertResponseIsSuccessful();

        $interaction = $this->getEntityManager()->getRepository(BookInteraction::class)->findOneBy($criteria);
        self::assertNotNull($interaction, 'No Interaction found matching your criteria');
    }

    protected function getBookById(int $id): ?Book
    {
        $book = $this->getEntityManager()->getRepository(Book::class)->findOneBy(['id' => $id]);

        /** @var Book|null */
        return $book;
    }

    private function getSerializer(): SerializerInterface
    {
        $service = self::getContainer()->get('serializer');
        assert($service instanceof SerializerInterface);

        return $service;
    }

    private function getReadingStates(string $bookUuid, int $percent = 50): ReadingStates
    {
        assert($percent >= 0 && $percent <= 100, 'Percent must be between 0 and 100');

        $state = new ReadingState();
        $state->lastModified = new \DateTimeImmutable();
        $state->currentBookmark = new Bookmark();
        $state->currentBookmark->contentSourceProgressPercent = $state->currentBookmark->progressPercent =  $percent;
        $state->currentBookmark->location = new ReadingStateLocation();
        $state->currentBookmark->location->source = BookFixture::BOOK_PAGE_REFERENCE;
        $state->currentBookmark->lastModified = new \DateTime();
        $state->entitlementId = $bookUuid;
        $state->statusInfo = new ReadingStateStatusInfo();
        $state->statusInfo->status = $percent === 100 ? ReadingStateStatusInfo::STATUS_FINISHED : ReadingStateStatusInfo::STATUS_READING;
        $state->statusInfo->lastModified = $state->lastModified;
        $state->statistics = new ReadingStateStatistics();
        $state->statistics->remainingTimeMinutes = 100 * ($percent/100);
        $state->statistics->spentReadingMinutes = 100 - $state->statistics->remainingTimeMinutes;
        $state->statistics->lastModified = $state->lastModified;

        return new ReadingStates([$state]);
    }

    /**
     * @return array<array{0: int, 1: ReadingStates, 2: ReadingStateCriteria}>
     */
    public function readingStatesProvider(): array
    {
        return [
            [
                BookFixture::ID,
                $this->getReadingStates(BookFixture::UUID, 50),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 15,
                    'finished' => false,
                ]
            ],
            [
                BookFixture::ID,
                $this->getReadingStates(BookFixture::UUID, 100),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 30,
                    'finished' => true,
                ]
            ],
        ];
    }

}