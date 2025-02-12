<?php

namespace App\Ai\Prompt;

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
        return $this->getPromptWithoutInstructions().'
The output must be only valid JSON format. It must be an object with one key named "tags" containing an array of genres and tags for this book in strings. 
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

        if (!is_array($items) || !isset($items['tags']) || !is_array($items['tags'])) {
            return [$result];
        }

        return array_filter($items['tags'], fn ($item) => $item !== null);
    }
}
