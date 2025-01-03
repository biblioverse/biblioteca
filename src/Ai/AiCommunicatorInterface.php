<?php

namespace App\Ai;

use App\Ai\Prompt\BookPromptInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_communicator', ['priority' => 20])]
interface AiCommunicatorInterface
{
    public function initialise(string $basePrompt): void;

    public function interrogate(BookPromptInterface $prompt): string|array;

    public function isEnabled(): bool;
}
