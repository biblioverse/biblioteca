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
use App\Tests\Controller\Kobo\KoboControllerTestCase;
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
        $json = $serializer->serialize($readingStates, 'json');
        $client?->request('PUT', sprintf('/kobo/%s/v1/library/%s/state', KoboFixture::ACCESS_KEY, $book->getUuid()), [], [], [], $json);

        self::assertResponseIsSuccessful();

        $interaction = $this->getEntityManager()->getRepository(BookInteraction::class)->findOneBy($criteria);
        self::assertNotNull($interaction, 'No Interaction found matching your criteria');
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

    private static function getReadingStates(string $bookUuid, int $percent = 50): ReadingStates
    {
        assert($percent >= 0 && $percent <= 100, 'Percent must be between 0 and 100');

        $state = new ReadingState();
        $state->lastModified = new \DateTimeImmutable();
        $state->currentBookmark = new Bookmark();
        $state->currentBookmark->contentSourceProgressPercent = $state->currentBookmark->progressPercent = $percent;
        $state->currentBookmark->location = new ReadingStateLocation();
        $state->currentBookmark->location->source = BookFixture::BOOK_PAGE_REFERENCE;
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
                self::getReadingStates(BookFixture::UUID, 50),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 15,
                    'readStatus' => ReadStatus::Started,
                ],
            ],
            [
                BookFixture::ID,
                self::getReadingStates(BookFixture::UUID, 100),
                [
                    'book' => BookFixture::ID,
                    'readPages' => 30,
                    'readStatus' => ReadStatus::Finished,
                ],
            ],
            [
                BookFixture::ID,
                self::getReadingStates(BookFixture::UUID, 0),
                [
                    'book' => BookFixture::ID,
                    'readPages' => null,
                    'readStatus' => ReadStatus::NotStarted,
                ],
            ],
        ];
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
