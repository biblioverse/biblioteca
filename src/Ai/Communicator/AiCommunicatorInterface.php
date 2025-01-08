<?php

namespace App\Ai\Communicator;

use App\Entity\AiModel;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_communicator', ['priority' => 20])]
interface AiCommunicatorInterface
{
    public function initialise(AiModel $model): void;

    public function getAiModel(): AiModel;

    public function interrogate(string $prompt): string;
}
