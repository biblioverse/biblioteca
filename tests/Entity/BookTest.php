<?php

namespace App\Tests\Entity;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use App\Repository\BookRepository;
use App\Tests\TestCaseHelperTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->deleteAllInteractions();
    }

    public function testGenUuid(): void
    {
        $book = $this->getBook();
        $originalUuid = $book->getUuid();
        $book = clone $book;
        $book->setChecksum(md5($book->getUuid()));
        $book->setTitle('My title');
        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();

        self::assertNotEquals($originalUuid, $book->getUuid());
    }

    public static function getUserProvider(): array
    {
        return [
            [ReadingList::NotDefined, ReadStatus::Finished, [
                'favorite' => [],
                'hidden' => [],
                'read' => [0 => 'userid'],
            ]],
            [ReadingList::Ignored, ReadStatus::NotStarted, [
                'favorite' => [],
                'hidden' => [0 => 'userid'],
                'read' => [],
            ]],
            [ReadingList::ToRead, ReadStatus::Started, [
                'favorite' => [0 => 'userid'],
                'hidden' => [],
                'read' => [],
            ]],
        ];
    }

    /**
     * @param array{'favorite': array<string>,'hidden': array<string>,'read': array<string>} $expected
     */
    #[DataProvider('getUserProvider')]
    public function testGetUsers(ReadingList $readingList, ReadStatus $readStatus, array $expected): void
    {
        $book = clone $this->getBook();
        $book->setChecksum(md5($book->getUuid()));
        $book->setTitle('My title');

        $interaction = $this->createInteraction($book);
        $interaction->setReadingList($readingList);
        $interaction->setReadStatus($readStatus);

        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();

        $userId = $this->getKoboDevice()->getUser()->getId();
        foreach ($expected as &$value) {
            if ($value !== [] && $value[0] === 'userid') {
                $value[0] = $userId;
            }
        }

        $result = (array) $book->getUsers();
        ksort($result);
        ksort($expected);
        self::assertSame($expected, $result);
    }

    public function testUnique(): void
    {
        $book = clone $this->getBook();
        $book->setChecksum(md5($book->getUuid()));
        $book->setTitle('My title');

        $interaction = $this->createInteraction($book);
        $interaction->setReadingList(ReadingList::Ignored);

        $interaction = $this->createInteraction($book);
        $interaction->setReadStatus(ReadStatus::Started);

        try {
            $this->getEntityManager()->persist($book);
            $this->getEntityManager()->flush();
            self::fail('Unique expected exception');
        } catch (\Exception $e) {
            self::assertStringContainsString('unique', strtolower($e->getMessage()));
        }
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->deleteAllInteractions();
        $this->getService(BookRepository::class)->deleteByTitle('My title');
        parent::tearDown();
    }

    private function createInteraction(Book $book): BookInteraction
    {
        $interaction = new BookInteraction();
        $interaction->setUser($this->getKoboDevice()->getUser());
        $interaction->setBook($book);
        $this->getEntityManager()->persist($interaction);
        $book->addBookInteraction($interaction);

        return $interaction;
    }
}
