<?php

namespace App\Ai\Communicator;

use App\Config\ConfigValue;
use App\Entity\AiModel;
use App\Repository\AiModelRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CommunicatorDefiner
{
    /**
     * @param iterable<AiCommunicatorInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.ai_communicator')]
        private readonly iterable $handlers,
        private readonly ConfigValue $configValue,
        private readonly AiModelRepository $aiModelRepository,
    ) {
    }

    public function getCommunicator(AiAction $action): ?AiCommunicatorInterface
    {
        $modelId = $this->configValue->resolve($action->value);

        if ($modelId === null) {
            return null;
        }

        $model = $this->aiModelRepository->find($modelId);

        if (!$model instanceof AiModel) {
            return null;
        }

        foreach ($this->handlers as $handler) {
            if ($model->getType() === $handler::class) {
                $handler->initialise($model);

                return $handler;
            }
        }

        return null;
    }

    public function getSpecificCommunicator(AiModel $model): AiCommunicatorInterface
    {
        foreach ($this->handlers as $handler) {
            if ($model->getType() === $handler::class) {
                $handler->initialise($model);

                return $handler;
            }
        }

        throw new \Exception('No handler found for model '.$model->getType());
    }
}
