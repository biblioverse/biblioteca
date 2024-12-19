<?php

namespace App\Tests\Suggestion;

use App\Ai\CommunicatorDefiner;
use App\Ai\TestCommunicator;
use App\Entity\Book;
use App\Suggestion\SummaryPrompt;
use App\Suggestion\TagPrompt;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommunicatorTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetCommunicator(): void
    {
        $service = self::getContainer()->get(CommunicatorDefiner::class);
        assert($service instanceof CommunicatorDefiner);

        $communicator = $service->getCommunicator();

        assert($communicator instanceof TestCommunicator);

        $book = new Book();
        $book->setTitle('The Hobbit');
        $book->setAuthors(['J.R.R. Tolkien']);
        $book->setSerie('The Lord of the Rings');
        $book->setSerieIndex(1);
        $prompt = new SummaryPrompt($book, null);
        $result = $communicator->interrogate($prompt);
        if (!is_string($result)) {
            self::fail('Expected array, got '.gettype($result));
        }
        self::assertStringContainsString('test summary', $result);

        $prompt = new TagPrompt($book, null);
        $result = $communicator->interrogate($prompt);
        if (!is_array($result)) {
            self::fail('Expected array, got '.gettype($result));
        }
        self::assertEquals(['keyword', 'keyword2'], $result);
    }
}
