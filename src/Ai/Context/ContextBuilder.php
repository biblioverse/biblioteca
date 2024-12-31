<?php

namespace App\Ai\Context;

use App\Ai\Prompt\AbstractBookPrompt;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
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

    public function getContext(AbstractBookPrompt $abstractBookPrompt, ?OutputInterface $output = null): AbstractBookPrompt
    {
        if (!$output instanceof OutputInterface) {
            $output = new NullOutput();
        }

        $hasContext = false;
        $prompt = "Use the following pieces of context to answer the query at the end. If you don't know the answer, don't try to make up an answer. Context:
---------------------
";
        foreach ($this->handlers as $handler) {
            if ($handler->isEnabled()) {
                try {
                    $prompt .= $handler->getContextForPrompt($abstractBookPrompt->getBook());
                    $hasContext = true;
                } catch (\Exception $e) {
                    $output->writeln('An error happened while fetching context with '.($handler::class).': '.$e->getMessage());
                }
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
