<?php

namespace App\Tests\Command;

use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Command\BooksSeriesHarmonizeCommand;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BooksSeriesHarmonizeCommandTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BookRepository&MockObject $bookRepository;
    private CommunicatorDefiner&MockObject $communicatorDefiner;
    private AiCommunicatorInterface&MockObject $communicator;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->communicatorDefiner = $this->createMock(CommunicatorDefiner::class);
        $this->communicator = $this->createMock(AiCommunicatorInterface::class);
        $this->communicatorDefiner->method('getCommunicator')->willReturn($this->communicator);
    }

    public function testDryRunDoesNotFlush(): void
    {
        $book = $this->createBook(42, 'Foundation 1', ['Isaac Asimov'], 'en');
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['title' => 'Foundation', 'serie' => 'Foundation', 'serieIndex' => 1]])
        );

        $this->em->expects($this->never())->method('flush');

        $tester = new CommandTester(new BooksSeriesHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('--apply', $tester->getDisplay());
    }

    public function testApplyModeSetsSeriesInfo(): void
    {
        $book = $this->createBook(42, 'Foundation 1', ['Isaac Asimov'], 'en');
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['title' => 'Foundation', 'serie' => 'Foundation', 'serieIndex' => 1]])
        );

        $this->em->expects($this->once())->method('flush');

        $tester = new CommandTester(new BooksSeriesHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--apply' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertSame('Foundation', $book->getSerie());
        self::assertSame(1.0, $book->getSerieIndex());
        self::assertSame('Foundation', $book->getTitle());
    }

    public function testPromptContainsKeyInformation(): void
    {
        $book = $this->createBook(42, 'Foundation 1', ['Isaac Asimov'], 'en');
        $this->bookRepository->method('findAll')->willReturn([$book]);

        $capturedPrompt = '';
        $this->communicator->expects($this->once())
            ->method('interrogate')
            ->with(self::callback(function (string $prompt) use (&$capturedPrompt): bool {
                $capturedPrompt = $prompt;

                return true;
            }))
            ->willReturn((string) json_encode(['42' => ['title' => null, 'serie' => null, 'serieIndex' => null]]));

        $tester = new CommandTester(new BooksSeriesHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute([]);

        self::assertStringContainsString('Foundation 1', $capturedPrompt);
        self::assertStringContainsString('Isaac Asimov', $capturedPrompt);
        self::assertStringContainsString('en', $capturedPrompt);
    }

    public function testExcludeSkipsBook(): void
    {
        $book = $this->createBook(42, 'Foundation 1', ['Isaac Asimov'], 'en');
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['title' => 'Foundation', 'serie' => 'Foundation', 'serieIndex' => 1]])
        );

        $tester = new CommandTester(new BooksSeriesHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--apply' => true, '--exclude' => '42']);

        $tester->assertCommandIsSuccessful();
        self::assertSame('Foundation 1', $book->getTitle());
        self::assertNull($book->getSerie());
        self::assertStringContainsString('SKIP', $tester->getDisplay());
    }

    /**
     * @param string[] $authors
     */
    private function createBook(int $id, string $title, array $authors, string $language): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthors($authors);
        $book->setLanguage($language);
        $book->setChecksum('checksum-'.$id);
        $ref = new \ReflectionProperty(Book::class, 'id');
        $ref->setValue($book, $id);

        return $book;
    }
}
