<?php

namespace App\Ai\Context;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Prompt\BookPromptInterface;
use App\Ai\Prompt\TagPrompt;
use App\Entity\AiModel;
use App\Entity\Book;

class PerplexicaContextBuilder implements ContextBuildingInterface
{
    private ?AiCommunicatorInterface $communicator = null;

    public function __construct(CommunicatorDefiner $communicatorDefiner)
    {
        $this->communicator = $communicatorDefiner->getCommunicator(AiAction::Context);
    }

    #[\Override]
    public function isEnabled(AiModel $aiModel, ?Book $book = null): bool
    {
        return $book instanceof Book && $this->communicator instanceof AiCommunicatorInterface;
    }

    #[\Override]
    public function getContextForPrompt(BookPromptInterface $prompt): string
    {
        if (!$this->communicator instanceof AiCommunicatorInterface) {
            return '';
        }

        if ($prompt instanceof TagPrompt) {
            return $this->communicator->interrogate($prompt->getPromptWithoutInstructions());
        }

        return $this->communicator->interrogate($prompt->getPrompt());
    }
}
