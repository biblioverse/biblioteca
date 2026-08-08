<?php

namespace App\Tests\Service;

use App\DataFixtures\BookFixture;
use App\Service\BookManager;
use App\Tests\TestCaseHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookManagerTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    private function getTestEpubPath(): string
    {
        return \dirname(__DIR__, 1).'/Resources/books/'.BookFixture::BOOK_ODYSSEY_FILENAME;
    }

    public function testUpdateBookMetadataUpdatesFieldsFromEpub(): void
    {
        $bookManager = $this->getService(BookManager::class);

        // Get an existing book from fixtures (The Odyssey)
        $book = $this->getBook(['uuid' => BookFixture::UUID]);

        // Store original values for cleanup (including slug since Gedmo won't auto-restore it)
        $originalTitle = $book->getTitle();
        $originalSlug = $book->getSlug();
        $originalSummary = $book->getSummary();
        $originalAuthors = $book->getAuthors();

        try {
            // Modify the book's metadata to simulate outdated data
            $book->setTitle('Outdated Title');
            $book->setSummary('Outdated summary');
            $book->setAuthors(['Wrong Author']);
            $this->getEntityManager()->flush();

            // Update metadata from the EPUB file
            $file = new \SplFileInfo($this->getTestEpubPath());
            $updatedBook = $bookManager->updateBookMetadata($book, $file);

            // The metadata should be restored from the EPUB
            self::assertNotEquals('Outdated Title', $updatedBook->getTitle());
            self::assertStringContainsString('Odyssey', $updatedBook->getTitle());
            self::assertContains('Homer', $updatedBook->getAuthors());
            self::assertSame($book->getId(), $updatedBook->getId(), 'Should update the same book entity');
        } finally {
            // Restore original values to avoid affecting other tests
            $book->setTitle($originalTitle);
            $book->setSlug($originalSlug);
            $book->setSummary($originalSummary);
            $book->setAuthors($originalAuthors);
            $this->getEntityManager()->flush();
        }
    }

    public function testUpdateBookMetadataPreservesFieldsWhenEpubHasNoData(): void
    {
        $bookManager = $this->getService(BookManager::class);

        // Get an existing book from fixtures
        $book = $this->getBook(['uuid' => BookFixture::UUID]);

        // Set a custom summary (The Odyssey EPUB has no dc:description)
        $customSummary = 'My custom summary that should be preserved';
        $book->setSummary($customSummary);
        $this->getEntityManager()->flush();

        // Update metadata from the EPUB file
        $file = new \SplFileInfo($this->getTestEpubPath());
        $updatedBook = $bookManager->updateBookMetadata($book, $file);

        // The summary should be preserved since EPUB has no description
        self::assertEquals($customSummary, $updatedBook->getSummary());
    }
}
