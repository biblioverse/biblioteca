<?php

namespace App\Suggestion;

use App\Entity\Book;
use App\Entity\User;

class TagPrompt extends AbstractBookPrompt
{
    public const DEFAULT_KEYWORD_PROMPT = 'Imagine you want to tag a book. Can you cite the most probable categories for the following book: {book}? Display only the results, separating each term with a newline, and return an empty string if you don\'t know. This way, your answer can be parsed.';

    public function getPrompt(Book $book, ?User $user): string
    {
        $prompt = self::DEFAULT_KEYWORD_PROMPT;

        if ($user instanceof User) {
            $prompt = $user->getBookKeywordPrompt() ?? self::DEFAULT_KEYWORD_PROMPT;
        }

        return $this->replaceBookOccurrence($book, $prompt);
    }
}
