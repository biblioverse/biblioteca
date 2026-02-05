<?php

namespace App\Tests\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class BooksCleanupCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->bookRepository = static::getContainer()->get(BookRepository::class);
    }

    public function testListOrphanedBooks(): void
    {
        // Create an orphaned book (file doesn't exist)
        $orphanBook = $this->createOrphanedBook();

        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);

        $command = $application->find('books:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('orphaned', strtolower($output));
        self::assertStringContainsString('Orphan Test Book', $output);

        // Verify book still exists (no deletion without --delete)
        $book = $this->bookRepository->find($orphanBook->getId());
        self::assertNotNull($book, 'Book should still exist without --delete flag');

        // Cleanup
        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }

    public function testDeleteOrphanedBooks(): void
    {
        // Create an orphaned book (file doesn't exist)
        $orphanBook = $this->createOrphanedBook();
        $orphanId = $orphanBook->getId();

        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);

        $command = $application->find('books:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--delete' => true]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleted', $output);

        // Verify book was deleted
        $book = $this->bookRepository->find($orphanId);
        self::assertNull($book, 'Orphaned book should be deleted with --delete flag');
    }

    public function testNoOrphanedBooks(): void
    {
        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);

        $command = $application->find('books:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No orphaned books found', $output);
    }

    private function createOrphanedBook(): Book
    {
        $book = new Book();
        $book->setTitle('Orphan Test Book');
        $book->setBookPath('nonexistent/path/');
        $book->setBookFilename('nonexistent-file.epub');
        $book->setExtension('epub');
        $book->setChecksum('test-checksum-'.uniqid());
        $book->addAuthor('Test Author');

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $book;
    }
}
