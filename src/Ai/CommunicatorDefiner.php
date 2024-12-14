<?php

namespace App\Ai;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CommunicatorDefiner
{
    /**
     * @param iterable<AiCommunicatorInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.ai_communicator')]
        private readonly iterable $handlers,
    ) {
    }

    public function getCommunicator(): ?AiCommunicatorInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isEnabled()) {
                $handler->initialise('You are a helpful factual librarian that only refers to verifiable content to provide real answers about existing books.');

                return $handler;
            }
        }

        return null;
    }
}
