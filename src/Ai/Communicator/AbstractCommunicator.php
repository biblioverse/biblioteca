<?php

namespace App\Ai\Communicator;

use App\Entity\AiModel;

abstract class AbstractCommunicator implements AiCommunicatorInterface
{
    protected AiModel $aiModel;

    #[\Override]
    public function initialise(AiModel $model): void
    {
        $this->aiModel = $model;
    }

    #[\Override]
    public function getAiModel(): AiModel
    {
        return $this->aiModel;
    }
}
