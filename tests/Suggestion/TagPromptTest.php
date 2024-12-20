<?php

namespace App\Tests\Suggestion;

use App\Entity\Book;
use App\Suggestion\TagPrompt;
use PHPUnit\Framework\TestCase;

class TagPromptTest extends TestCase
{
    public function testGetPrompt(): void
    {
        $book = new Book();
        $book->setTitle('The Hobbit');
        $book->setAuthors(['J.R.R. Tolkien']);
        $book->setSerie('The Lord of the Rings');
        $book->setSerieIndex(1);

        $summaryPrompt = new TagPrompt($book, null);

        $prompt = $summaryPrompt->getPrompt();
        self::assertStringContainsString($book->getTitle(), $prompt);
        self::assertStringContainsString('in the series', $prompt);
    }
}
