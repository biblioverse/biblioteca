<?php

namespace App\Ai;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_communicator', ['priority' => 20])]
interface AiCommunicatorInterface
{
    public function initialise(string $basePrompt): void;

    public function sendMessageForString(string $message): string;

    public function sendMessageForArray(string $message): array;

    public function isEnabled(): bool;
}
