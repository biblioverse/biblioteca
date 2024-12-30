<?php

namespace App\Ai;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CommunicatorDefiner
{
    public const BASE_PROMPT = "
    As a highly skilled and experienced librarian AI model, I'm here to help you tag and summarize books as close to the
    original as possible. I will never make up any information. I will only use the information you provide me.
    I will communicate with you primarily using your preferred language.";

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
                $handler->initialise(self::BASE_PROMPT);

                return $handler;
            }
        }

        return null;
    }
}
