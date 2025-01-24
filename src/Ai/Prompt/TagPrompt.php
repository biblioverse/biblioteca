<?php

namespace App\Ai\Prompt;

use App\Entity\User;

class TagPrompt extends AbstractBookPrompt
{
    #[\Override]
    public function initialisePrompt(): void
    {
        $prompt = $this->config->resolve('AI_TAG_PROMPT');

        if ($this->user instanceof User) {
            $prompt = $this->user->getBookKeywordPrompt() ?? $prompt;
        }
        $prompt .= '

The output must be only valid JSON format. It must be an object with one key named "genres" containing an array of genres and tags for this book in strings. Do not add anything else than json. Do not add any other text or comment.';

        $this->prompt = $this->replaceBookOccurrence($prompt);
    }

    #[\Override]
    public function convertResult(string $result): array
    {
        try {
            $result = trim($result, '´`');
            if (str_starts_with($result, 'json')) {
                $result = substr($result, 4);
            }
            $items = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($items) || !isset($items['genres']) || !is_array($items['genres'])) {
            return [$result];
        }

        return array_filter($items['genres'], fn ($item) => $item !== null);
    }
}
