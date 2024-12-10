<?php

namespace App\Tests\Suggestion;

use App\Entity\Book;
use App\Entity\User;
use App\Suggestion\TagPrompt;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TagPromptTest extends TestCase
{
    public function testGetPrompt(): void
    {
        $book = new Book();
        $book->setTitle('The Hobbit');
        $book->setAuthors(['J.R.R. Tolkien']);
        $book->setSerie('The Lord of the Rings');
        $book->setSerieIndex(1);

        $user = new User();
        $user->setOpenAIKey('test');

        $summaryPrompt = new TagPrompt(new NullLogger());
        $prompt = $summaryPrompt->getPrompt($book, $user);
        self::assertStringContainsString($book->getTitle(), $prompt);
        self::assertStringContainsString('in the series', $prompt);
    }

    public function testPromptResultToTags(): void
    {
        $tagPrompt = new TagPrompt(new NullLogger());
        $tags = $tagPrompt->promptResultToTags("- fantasy\n- adventure\n - classic");
        self::assertSame(['fantasy', 'adventure', 'classic'], $tags);
    }
}
