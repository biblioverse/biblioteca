<?php

namespace App\Ai\Prompt;

use App\Ai\GenreList;

class TagPrompt extends AbstractBookPrompt
{
    #[\Override]
    public function initialisePrompt(): void
    {
        $prompt = $this->config->resolve('AI_TAG_PROMPT');

        $this->prompt = $this->replaceBookOccurrence($prompt ?? '');
    }

    #[\Override]
    public function getPrompt(): string
    {
        $genres = GenreList::getForLanguage($this->language);
        $genresList = implode(', ', $genres);

        return 'You are an expert librarian specializing in book categorization.

'.$this->getPromptWithoutInstructions().'

IMPORTANT: You must select ONE main genre from this list: ['.$genresList.']

Then add 3-5 specific tags that describe this book (themes, setting, style, etc.).

The output must be only valid JSON format: {"genre": "MainGenre", "tags": ["tag1", "tag2", "tag3"]}
The genre MUST be from the list above. Tags should be in the same language.
Do not add anything else than json. Do not add any other text or comment.';
    }

    public function getPromptWithoutInstructions(): string
    {
        return $this->prompt;
    }

    #[\Override]
    public function convertResult(string $result): array
    {
        // remove deepseek thinking
        $result = preg_replace('/<think>.*?<\/think>/s', '', $result);

        try {
            $result = trim((string) $result, "´`\n\r\t\v\0");
            if (str_starts_with($result, 'json')) {
                $result = substr($result, 4);
            }
            $items = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($items)) {
            return [$result];
        }

        $tags = [];

        // Add main genre first if present and valid
        if (isset($items['genre']) && is_string($items['genre']) && $items['genre'] !== '') {
            $tags[] = $items['genre'];
        }

        // Add other tags
        if (isset($items['tags']) && is_array($items['tags'])) {
            foreach ($items['tags'] as $tag) {
                if ($tag !== null && $tag !== '') {
                    $tags[] = $tag;
                }
            }
        }

        return array_unique($tags);
    }
}
