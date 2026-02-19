<?php

namespace App\Tests\Command;

use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Command\BooksAuthorsHarmonizeCommand;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BooksAuthorsHarmonizeCommandTest extends TestCase
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
        $book = $this->createBook(1, 'The Lord of the Rings', ['Tolkien, J.R.R.']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['Tolkien, J.R.R.' => 'J.R.R. Tolkien'])
        );

        $this->em->expects($this->never())->method('flush');

        $tester = new CommandTester(new BooksAuthorsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('--apply', $tester->getDisplay());
    }

    public function testApplyModeUpdatesAuthorNames(): void
    {
        $book = $this->createBook(1, 'The Lord of the Rings', ['Tolkien, J.R.R.']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['Tolkien, J.R.R.' => 'J.R.R. Tolkien'])
        );

        $this->em->expects($this->once())->method('flush');

        $tester = new CommandTester(new BooksAuthorsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--apply' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertSame(['J.R.R. Tolkien'], $book->getAuthors());
    }

    public function testPromptContainsAuthorNames(): void
    {
        $book = $this->createBook(1, 'Fondation', ['Asimov, Isaac']);
        $this->bookRepository->method('findAll')->willReturn([$book]);

        $capturedPrompt = '';
        $this->communicator->expects($this->once())
            ->method('interrogate')
            ->with(self::callback(function (string $prompt) use (&$capturedPrompt): bool {
                $capturedPrompt = $prompt;

                return true;
            }))
            ->willReturn((string) json_encode(['Asimov, Isaac' => 'Asimov, Isaac']));

        $tester = new CommandTester(new BooksAuthorsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute([]);

        self::assertStringContainsString('Asimov, Isaac', $capturedPrompt);
    }

    public function testExcludeSkipsAuthor(): void
    {
        $book = $this->createBook(1, 'It', ['stephen king']);
        $this->bookRepository->method('findAll')->willReturn([$book]);
        $this->communicator->method('interrogate')->willReturn(
            (string) json_encode(['Stephen King' => 'Stephen Edwin King'])
        );

        $tester = new CommandTester(new BooksAuthorsHarmonizeCommand($this->em, $this->bookRepository, $this->communicatorDefiner));
        $tester->execute(['--apply' => true, '--exclude' => 'Stephen King']);

        $tester->assertCommandIsSuccessful();
        self::assertSame(['Stephen King'], $book->getAuthors());
    }

    /**
     * @param string[] $authors
     */
    private function createBook(int $id, string $title, array $authors): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthors($authors);
        $book->setChecksum('checksum-'.$id);
        $ref = new \ReflectionProperty(Book::class, 'id');
        $ref->setValue($book, $id);

        return $book;
    }
}
