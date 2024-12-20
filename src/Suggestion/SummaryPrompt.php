<?php

namespace App\Suggestion;

use App\Entity\User;

class SummaryPrompt extends AbstractBookPrompt
{
    public const DEFAULT_KEYWORD_PROMPT = 'Can you write a short summary for the following book: {book}?';

    public function initialisePrompt(): void
    {
        $prompt = self::DEFAULT_KEYWORD_PROMPT;

        if ($this->user instanceof User) {
            $prompt = $this->user->getBookSummaryPrompt() ?? self::DEFAULT_KEYWORD_PROMPT;
        }

        $prompt .= ' Remember to keep it short and concise. Do not add any comment or opinion, only the summary must be returned.';

        $this->prompt = $this->replaceBookOccurrence($prompt);
    }

    public function convertResult(string $result): string
    {
        return $result;
    }
}
