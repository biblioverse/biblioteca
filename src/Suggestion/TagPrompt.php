<?php

namespace App\Suggestion;

use App\Entity\User;

class TagPrompt extends AbstractBookPrompt
{
    public const DEFAULT_KEYWORD_PROMPT = 'I want to tag a book. Can you cite the genres and tags for the following book: {book}? 
     Translate the genres in french.
    ';

    public function initialisePrompt(): void
    {
        $prompt = self::DEFAULT_KEYWORD_PROMPT;

        if ($this->user instanceof User) {
            $prompt = $this->user->getBookKeywordPrompt() ?? self::DEFAULT_KEYWORD_PROMPT;
        }
        $prompt .= '

The output must be only valid JSON format. It must be an object with one key named "genres" containaing an array of genres and tags for this book in strings. Do not add anything else than json. Do not add any other text or comment.';

        $this->prompt = $this->replaceBookOccurrence($prompt);
    }

    public function convertResult(string $result): array
    {
        try {
            $result = trim($result, '´`');
            $items = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($items) || !isset($items['genres']) || !is_array($items['genres'])) {
            return [$result];
        }

        return array_filter($items['genres']);
    }
}
