<?php

namespace App\Ai;

use App\Ai\Prompt\BookPromptInterface;
use App\Ai\Prompt\SummaryPrompt;
use App\Ai\Prompt\TagPrompt;
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

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->environment === 'test';
    }

    #[\Override]
    public function initialise(string $basePrompt): void
    {
        // Do nothing
    }

    #[\Override]
    public function interrogate(BookPromptInterface $prompt): string|array
    {
        return match ($prompt::class) {
            SummaryPrompt::class => 'This is a test summary',
            TagPrompt::class => ['keyword', 'keyword2'],
            default => 'This is a mistake',
        };
    }
}
