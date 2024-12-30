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
        $hasContext = false;
        $prompt = "Use the following pieces of context to answer the query at the end. If you don't know the answer, don't try to make up an answer. Context:
---------------------
";
        foreach ($this->handlers as $handler) {
            if ($handler->isEnabled()) {
                $hasContext = true;
                $prompt .= $handler->getContextForPrompt($abstractBookPrompt->getBook());
            }
        }
        $prompt .= '
---------------------
Given the context information and not prior knowledge, answer the query.
Query: '.$abstractBookPrompt->getPrompt();

        if ($hasContext) {
            $abstractBookPrompt->setPrompt($abstractBookPrompt->replaceBookOccurrence($prompt));
        }

        return $abstractBookPrompt;
    }
}
