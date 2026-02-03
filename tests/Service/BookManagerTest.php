<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManagerInterface;
use App\Service\BookManager;
use App\Tests\TestCaseHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookManagerTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    private BookManager $bookManager;
    private BookFileSystemManagerInterface $fileSystemManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->bookManager = $this->getService(BookManager::class);
        $this->fileSystemManager = $this->getService(BookFileSystemManagerInterface::class);
    }

    public function testCreateBookSetsExtension(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBook($file);

        self::assertSame('epub', $book->getExtension(), 'Extension should be set from file');
    }

    public function testCreateBookSetsTitle(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBook($file);

        self::assertNotEmpty($book->getTitle(), 'Title should be set');
    }

    public function testCreateBookSetsAuthors(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBook($file);

        self::assertNotEmpty($book->getAuthors(), 'Authors should be set');
    }

    public function testCreateBookSetsChecksum(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBook($file);

        self::assertNotEmpty($book->getChecksum(), 'Checksum should be set');
    }

    public function testUpdateBookMetadataClearsAndResetsAuthors(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        // Create a book with custom authors
        $book = new Book();
        $book->setExtension('epub');
        $book->setChecksum('test-checksum');
        $book->setBookPath('');
        $book->setBookFilename($file->getFilename());
        $book->addAuthor('Custom Author 1');
        $book->addAuthor('Custom Author 2');

        self::assertCount(2, $book->getAuthors(), 'Book should have 2 custom authors');

        // Update metadata from file
        $book = $this->bookManager->updateBookMetadata($book, $file);

        // Authors should now come from the file, not the custom ones
        self::assertNotContains('Custom Author 1', $book->getAuthors(), 'Custom authors should be cleared');
        self::assertNotContains('Custom Author 2', $book->getAuthors(), 'Custom authors should be cleared');
        self::assertNotEmpty($book->getAuthors(), 'Authors from file should be set');
    }

    public function testCreateBookWithoutMetadataSetsExtension(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBookWithoutMetadata($file);

        self::assertSame('epub', $book->getExtension(), 'Extension should be set from file');
    }

    public function testCreateBookWithoutMetadataSetsUnknownAuthor(): void
    {
        $file = new \SplFileInfo(__DIR__.'/../Resources/books/real-TheOdysses.epub');

        $book = $this->bookManager->createBookWithoutMetadata($file);

        self::assertContains('Unknown', $book->getAuthors(), 'Unknown author should be set');
    }
}
