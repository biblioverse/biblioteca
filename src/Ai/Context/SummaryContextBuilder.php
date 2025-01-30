<?php

namespace App\Ai\Context;

use App\Entity\AiModel;
use App\Entity\Book;

class SummaryContextBuilder implements ContextBuildingInterface
{
    #[\Override]
    public function isEnabled(AiModel $aiModel, ?Book $book = null): bool
    {
        return $book instanceof Book && trim((string) $book->getSummary()) !== '';
    }

    #[\Override]
    public function getContextForPrompt(Book $book): string
    {
        return 'This is the summary of the book: '.$book->getSummary();
    }
}
