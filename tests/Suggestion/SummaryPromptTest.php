<?php

namespace App\Tests\Suggestion;

use App\Entity\Book;
use App\Entity\User;
use App\Suggestion\SummaryPrompt;
use PHPUnit\Framework\TestCase;

class SummaryPromptTest extends TestCase
{
    private function getBook(): Book
    {
        $book = new Book();
        $book->setTitle('The Hobbit');
        $book->setAuthors(['J.R.R. Tolkien']);
        $book->setSerie('The Lord of the Rings');
        $book->setSerieIndex(1);

        return $book;
    }

    public function testGetPrompt(): void
    {
        $book = $this->getBook();

        $summaryPrompt = new SummaryPrompt($book, null);
        $prompt = $summaryPrompt->getPrompt();
        self::assertStringContainsString($book->getTitle(), $prompt);
        self::assertStringContainsString('in the series', $prompt);
    }

    public function testUserPrompt(): void
    {
        $book = $this->getBook();

        $user = new User();
        $user->setBookSummaryPrompt('Can you write a short summary for the following book: {book}?');

        $summaryPrompt = new SummaryPrompt($book, $user);

        $prompt = $summaryPrompt->getPrompt();
        self::assertStringContainsString('Can you write a short summary for the following book: "The Hobbit" by J.R.R. Tolkien number 1 in the series "The Lord of the Rings"?', $prompt);
    }
}
