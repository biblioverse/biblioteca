<?php

namespace App\Tests\Suggestion;

use App\Ai\CommunicatorDefiner;
use App\Ai\TestCommunicator;
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

        self::assertStringContainsString('test summary', $communicator->sendMessageForString('The Hobbit'));
        self::assertEquals($communicator->sendMessageForArray('The Hobbit'), ['keyword', 'keyword2']);
    }
}
