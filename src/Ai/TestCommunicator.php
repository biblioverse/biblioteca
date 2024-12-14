<?php

namespace App\Ai;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AutoconfigureTag('app.ai_communicator', ['priority' => 0])]
class TestCommunicator implements AiCommunicatorInterface
{
    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private readonly ?string $environment,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->environment === 'test';
    }

    public function initialise(string $basePrompt): void
    {
        // Do nothing
    }

    public function sendMessageForString(string $message): string
    {
        return 'This is a test summary';
    }

    public function sendMessageForArray(string $message): array
    {
        return ['keyword', 'keyword2'];
    }
}
