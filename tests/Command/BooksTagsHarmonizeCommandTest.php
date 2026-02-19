<?php

namespace App\Tests\Command;

use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Command\BooksTagsHarmonizeCommand;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BooksTagsHarmonizeCommandTest extends TestCase
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
        $book = $this->createBook(42, 'Dune', ['Frank Herbert'], ['Science Fiction', 'Space']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['genres' => ['Science-Fiction'], 'tags' => ['Désert', 'Politique']]])
        );

        $this->em->expects($this->never())->method('flush');

        $tester = new CommandTester(new BooksTagsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--language' => 'fr']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('--apply', $tester->getDisplay());
    }

    public function testApplyModeUpdatesBookTags(): void
    {
        $book = $this->createBook(42, 'Dune', ['Frank Herbert'], ['Science Fiction', 'Space']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['genres' => ['Science-Fiction'], 'tags' => ['Désert', 'Politique']]])
        );

        $this->em->expects($this->once())->method('flush');

        $tester = new CommandTester(new BooksTagsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--language' => 'fr', '--apply' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertSame(['Science-Fiction', 'Désert', 'Politique'], $book->getTags());
    }

    public function testPromptContainsKeyInformation(): void
    {
        $book = $this->createBook(42, 'Dune', ['Frank Herbert'], ['Science Fiction', 'Space']);
        $this->bookRepository->method('findAll')->willReturn([$book]);

        $capturedPrompt = '';
        $this->communicator->expects($this->once())
            ->method('interrogate')
            ->with(self::callback(function (string $prompt) use (&$capturedPrompt): bool {
                $capturedPrompt = $prompt;

                return true;
            }))
            ->willReturn((string) json_encode(['42' => ['genres' => ['Science-Fiction'], 'tags' => []]]));

        $tester = new CommandTester(new BooksTagsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--language' => 'fr']);

        self::assertStringContainsString('Dune', $capturedPrompt);
        self::assertStringContainsString('Frank Herbert', $capturedPrompt);
        self::assertStringContainsString('French', $capturedPrompt);
        self::assertStringContainsString('Science Fiction', $capturedPrompt);
    }

    public function testAddModePreservesExistingTags(): void
    {
        $book = $this->createBook(42, 'Dune', ['Frank Herbert'], ['Existing Tag']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['genres' => ['Science-Fiction'], 'tags' => ['Désert']]])
        );

        $tester = new CommandTester(new BooksTagsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--language' => 'fr', '--apply' => true, '--mode' => 'add']);

        $tester->assertCommandIsSuccessful();
        $tags = $book->getTags() ?? [];
        self::assertContains('Existing Tag', $tags);
        self::assertContains('Science-Fiction', $tags);
        self::assertContains('Désert', $tags);
    }

    public function testExcludeSkipsBook(): void
    {
        $book = $this->createBook(42, 'Dune', ['Frank Herbert'], ['Science Fiction']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['42' => ['genres' => ['Science-Fiction'], 'tags' => ['Désert']]])
        );

        $tester = new CommandTester(new BooksTagsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--language' => 'fr', '--apply' => true, '--exclude' => '42']);

        $tester->assertCommandIsSuccessful();
        self::assertSame(['Science Fiction'], $book->getTags());
        self::assertStringContainsString('SKIP', $tester->getDisplay());
    }

    /**
     * @param string[] $authors
     * @param string[] $tags
     */
    private function createBook(int $id, string $title, array $authors, array $tags): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthors($authors);
        $book->setTags($tags);
        $book->setChecksum('checksum-'.$id);
        $ref = new \ReflectionProperty(Book::class, 'id');
        $ref->setValue($book, $id);

        return $book;
    }
}
