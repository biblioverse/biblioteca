<?php

namespace App\Ai;

use App\Suggestion\BookPromptInterface;
use App\Suggestion\SummaryPrompt;
use App\Suggestion\TagPrompt;
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

    public function interrogate(BookPromptInterface $prompt): string|array
    {
        return match ($prompt::class) {
            SummaryPrompt::class => 'This is a test summary',
            TagPrompt::class => ['keyword', 'keyword2'],
            default => 'This is a mistake',
        };
    }
}
