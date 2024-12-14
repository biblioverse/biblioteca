<?php

namespace App\Suggestion;

use App\Entity\Book;
use App\Entity\User;

class SummaryPrompt extends AbstractBookPrompt
{
    public const DEFAULT_KEYWORD_PROMPT = 'Can you write a short summary for the following book: {book}?';

    public function getPrompt(Book $book, ?User $user): string
    {
        $prompt = self::DEFAULT_KEYWORD_PROMPT;

        if ($user instanceof User) {
            $prompt = $user->getBookSummaryPrompt() ?? self::DEFAULT_KEYWORD_PROMPT;
        }

        return $this->replaceBookOccurrence($book, $prompt);
    }
}
