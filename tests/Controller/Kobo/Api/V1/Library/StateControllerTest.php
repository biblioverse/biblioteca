<?php

namespace App\Tests\Controller\Kobo\Api\V1\Library;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Enum\ReadStatus;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingState;
use App\Kobo\Request\ReadingStateLocation;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatistics;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\StateResponse;
use App\Kobo\SyncToken\SyncTokenV1;
use App\Service\KoboSyncTokenExtractor;
use App\Tests\Controller\Kobo\KoboControllerTestCase;
use App\Tests\TestClock;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\SerializerInterface;

class StateControllerTest extends KoboControllerTestCase
{
    public function testOpen(): void
    {
        $client = static::getClient();
        $client?->setServerParameter('HTTP_CONNECTION', 'keep-alive');

        $book = $this->getBookById(BookFixture::ID);
        self::assertNotNull($book, 'Book '.BookFixture::ID.' not found');

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/'.$book->getUuid().'/state');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Connection', 'keep-alive');
    }

    public function testGetStateWithProxy(): void
    {
        // Take a book uuid that does not exist locally
        $unknownUuid = str_replace('0', 'b', BookFixture::UUID_JUNGLE_BOOK);
        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient($this->getStateResponseString($unknownUuid)));

        $client = static::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/'.$unknownUuid.'/state');

        self::assertResponseStatusCodeSame(307);
    }

    /**
     * @param ReadingStateCriteria $criteria
     */
    #[DataProvider('readingStatesProvider')]
    public function testPutState(int $bookId, ReadingStates $readingStates, array $criteria): void
    {
        $client = static::getClient();
        $serializer = $this->getSerializer();

        $book = $this->getBookById($bookId);
        self::assertNotNull($book, 'Book '.$bookId.' not found');

        // Not started will not create an interaction if there is none.
        // So we create one to test that the interaction is switched to NotStarted
        $bookInteraction = new BookInteraction();
        $bookInteraction->setBook($book);
        $bookInteraction->setUser($this->getKoboDevice()->getUser());
        $bookInteraction->setReadStatus(ReadStatus::Started);
        $this->getEntityManager()->persist($bookInteraction);
        $this->getEntityManager()->flush();

        $json = $serializer->serialize($readingStates, 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', KoboFixture::ACCESS_KEY, $book->getUuid()), [], [], [], $json);

        self::assertResponseIsSuccessful();
        self::assertResponseHasHeader('X-Kobo-Apitoken');
        self::assertResponseHeaderSame('X-Kobo-Apitoken', 'e30=');

        $interaction = $this->getEntityManager()->getRepository(BookInteraction::class)->findOneBy($criteria);
        self::assertNotNull($interaction, 'No Interaction found matching your criteria');

        $user = $this->getKoboDevice()->getUser();
        $bookmark = $user->getBookmarkForBook($book);
        if ($readingStates->readingStates[0]->currentBookmark === null) {
            self::assertNull($bookmark, 'Book '.$bookId.' has not bookmark');

            return;
        }
        self::assertNotNull($bookmark, 'Bookmark not found');
        $expectedLocation = $readingStates->readingStates[0]->currentBookmark->location;
        if ($expectedLocation !== null) {
            self::assertSame($expectedLocation->type, $bookmark->getLocationType(), 'Location type does not match');
            self::assertSame($expectedLocation->value, $bookmark->getLocationValue(), 'Location value does not match');
            self::assertSame($expectedLocation->source, $bookmark->getLocationSource(), 'Location source does not match');
        }
    }

    protected function getBookById(int $id): ?Book
    {
        return $this->getEntityManager()->getRepository(Book::class)->findOneBy(['id' => $id]);
    }

    private function getSerializer(): SerializerInterface
    {
        $service = self::getContainer()->get('serializer');
        self::assertInstanceOf(SerializerInterface::class, $service);

        return $service;
    }

    public function testPutStateWithProxy(): void
    {
        // Take a book uuid that does not exist locally
        $unknownUuid = str_replace('0', 'b', BookFixture::UUID_JUNGLE_BOOK);

        $client = self::getClient();

        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient($this->getStateResponseString($unknownUuid)));

        $json = $this->getSerializer()->serialize(self::getReadingStates($unknownUuid, 100), 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', KoboFixture::ACCESS_KEY, $unknownUuid), [], [], [], $json);
        self::assertResponseIsSuccessful();
    }

    public function testPutStateUnknownBook(): void
    {
        // Take a book uuid that does not exist locally
        $unknownUuid = str_replace('0', 'b', BookFixture::UUID_JUNGLE_BOOK);

        $client = self::getClient();

        $json = $this->getSerializer()->serialize(self::getReadingStates($unknownUuid, 100), 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', KoboFixture::ACCESS_KEY, $unknownUuid), [], [], [], $json);
        self::assertResponseStatusCodeSame(404);
    }

    private static function getReadingStates(string $bookUuid, int $percent = 50, ?string $locationType = null, ?string $locationValue = null): ReadingStates
    {
        assert($percent >= 0 && $percent <= 100, 'Percent must be between 0 and 100');

        $state = new ReadingState();
        $state->lastModified = new \DateTimeImmutable();
        $state->currentBookmark = new Bookmark();
        $state->currentBookmark->contentSourceProgressPercent = $state->currentBookmark->progressPercent = $percent;
        $state->currentBookmark->location = new ReadingStateLocation();
        $state->currentBookmark->location->source = BookFixture::BOOK_PAGE_REFERENCE;
        $state->currentBookmark->location->type = $locationType;
        $state->currentBookmark->location->value = $locationValue;
        $state->currentBookmark->lastModified = new \DateTime();
        $state->entitlementId = $bookUuid;
        $state->statusInfo = new ReadingStateStatusInfo();
        $state->statusInfo->status = match ($percent) {
            0 => ReadingStateStatusInfo::STATUS_READY_TO_READ,
            100 => ReadingStateStatusInfo::STATUS_FINISHED,
            default => ReadingStateStatusInfo::STATUS_READING,
        };
        $state->statusInfo->lastModified = $state->lastModified;
        $state->statistics = new ReadingStateStatistics();
        $state->statistics->remainingTimeMinutes = (int) (100 * ($percent / 100));
        $state->statistics->spentReadingMinutes = 100 - $state->statistics->remainingTimeMinutes;
        $state->statistics->lastModified = $state->lastModified;

        return new ReadingStates([$state]);
    }

    /**
     * @return array<array{0: int, 1: ReadingStates, 2: ReadingStateCriteria}>
     */
    public static function readingStatesProvider(): array
    {
        return [
            [
                BookFixture::ID,
                self::getReadingStates(BookFixture::UUID, 50, 'KoboSpan', 'kobo.13.5'),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 15,
                    'readStatus' => ReadStatus::Started,
                ],
            ],
            [
                BookFixture::ID,
                self::getReadingStates(BookFixture::UUID, 100, 'KoboSpan', 'kobo.99.9'),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 30,
                    'readStatus' => ReadStatus::Finished,
                ],
            ],
            [
                BookFixture::ID,
                self::getReadingStates(BookFixture::UUID, 0, 'KoboSpan', 'kobo.1.1'),
                [
                    'book' => BookFixture::ID,
                    'readPages' => null,
                    'readStatus' => ReadStatus::NotStarted,
                ],
            ],
        ];
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->getService(TestClock::class)->setTime(null);
        $this->deleteAllBookmarks();
        $this->deleteAllInteractions();
        $this->getKoboDevice()->setLastSyncToken(null);

        parent::tearDown();
    }

    public function testPutStateThenSyncReturnsChangedReadingState(): void
    {
        $client = static::getClient();
        $serializer = $this->getSerializer();

        $book = $this->getBookById(BookFixture::ID);
        self::assertNotNull($book, 'Book '.BookFixture::ID.' not found');

        // Step 1: Send PUT /state to update reading progress
        $readingStates = self::getReadingStates($book->getUuid(), 50, 'KoboSpan', 'kobo.13.5');
        $json = $serializer->serialize($readingStates, 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', KoboFixture::ACCESS_KEY, $book->getUuid()), [], [], [], $json);

        self::assertResponseIsSuccessful();

        // Flush to ensure the interaction is saved
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // Verify the interaction was created
        $interaction = $this->getEntityManager()->getRepository(BookInteraction::class)->findOneBy([
            'book' => $book,
            'user' => $this->getKoboDevice()->getUser(),
        ]);
        self::assertNotNull($interaction, 'BookInteraction was not created');
        self::assertNotNull($interaction->getUpdated(), 'BookInteraction updated timestamp is null');

        // Step 2: Simulate a sync token from a previous sync
        // Books were created at 2025-01-01, and we did an initial sync shortly after
        // The PUT just happened (updating BookInteraction.updated to "now")
        // So the sync token should reflect:
        //   - Books were already synced (lastModified after 2025-01-01)
        //   - But reading state wasn't synced recently (readingStateLastModified before the PUT)
        $syncToken = new SyncTokenV1();
        $syncToken->lastModified = new \DateTimeImmutable('2025-01-02 00:00:00');
        $syncToken->lastCreated = new \DateTimeImmutable('2025-01-02 00:00:00');
        $syncToken->readingStateLastModified = new \DateTimeImmutable('2025-01-02 00:00:00');

        $headers = $this->getService(KoboSyncTokenExtractor::class)->getTestHeader($syncToken);

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/sync', [], [], $headers);

        self::assertResponseIsSuccessful();

        $response = self::getJsonResponse();

        // Step 3: Verify ChangedReadingState is in the response
        $changedReadingStates = array_filter($response, fn ($item) => isset($item['ChangedReadingState']));
        self::assertNotEmpty($changedReadingStates, 'No ChangedReadingState found in sync response');

        // Verify it contains our book
        $foundBook = false;
        foreach ($changedReadingStates as $item) {
            $readingState = $item['ChangedReadingState'];
            $uuid = $readingState['EntitlementId'] ?? 'unknown';

            if ($uuid === $book->getUuid()) {
                $foundBook = true;

                // Verify the bookmark data matches what we sent
                self::assertSame(50, $readingState['CurrentBookmark']['ProgressPercent'], 'Progress percent does not match');
                self::assertSame('kobo.13.5', $readingState['CurrentBookmark']['Location']['Value'], 'Location value does not match');
                self::assertSame('KoboSpan', $readingState['CurrentBookmark']['Location']['Type'], 'Location type does not match');
                break;
            }
        }

        self::assertTrue($foundBook, 'Book '.$book->getUuid().' not found in ChangedReadingState entries');
    }

    private function getStateResponseString(Book|string $unknownUuid): string
    {
        $content = (new StateResponse($unknownUuid))->getContent();
        if ($content === false) {
            throw new \RuntimeException('Unable to generate a state response');
        }

        return $content;
    }
}
