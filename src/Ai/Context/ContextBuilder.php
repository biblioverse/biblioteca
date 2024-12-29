<?php

namespace App\Ai\Context;

use App\Ai\Prompt\AbstractBookPrompt;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ContextBuilder
{
    /**
     * @param iterable<ContextBuildingInteface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.ai_context_builder')]
        private readonly iterable $handlers,
    ) {
    }

    public function getContext(AbstractBookPrompt $abstractBookPrompt): AbstractBookPrompt
    {
        $prompt = "Use the following pieces of context to answer the question at the end. If you don't know the answer, don't try to make up an answer.";

        foreach ($this->handlers as $handler) {
            if ($handler->isEnabled()) {
                $prompt .= $handler->getContextForPrompt($abstractBookPrompt->getBook());
            }
        }

        $prompt .= $abstractBookPrompt->getPrompt();

        $abstractBookPrompt->setPrompt($abstractBookPrompt->replaceBookOccurrence($prompt));

        return $abstractBookPrompt;
    }
}
