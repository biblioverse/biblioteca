<?php

namespace App\Ai\Context;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Prompt\BookPromptInterface;
use App\Entity\AiModel;
use App\Entity\Book;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

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
        $cache = new FilesystemAdapter();

        return $cache->get('perplexica-'.$prompt->getBook()->getId(), function (ItemInterface $item) use($prompt): string {
            $item->expiresAfter(3600);

            return $this->communicator->interrogate($prompt->replaceBookOccurrence("Get me a very detailed summary of the content of the book {book}"));
        });
    }
}
