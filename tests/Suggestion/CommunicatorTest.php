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

        $prompt = new SummaryPrompt(new Book(), null);
        self::assertStringContainsString('test summary', $communicator->interrogate($prompt));

        $prompt = new TagPrompt(new Book(), null);
        self::assertEquals(['keyword', 'keyword2'], $communicator->interrogate($prompt));
    }
}
