<?php

namespace App\Ai\Prompt;

class SummaryPrompt extends AbstractBookPrompt
{
    #[\Override]
    public function initialisePrompt(): void
    {
        $prompt = $this->config->resolve('AI_SUMMARY_PROMPT');

        $this->prompt = $this->replaceBookOccurrence($prompt ?? '');
    }

    #[\Override]
    public function getPrompt(): string
    {
        return $this->getPromptWithoutInstructions().'Remember to keep it short and concise. Do not add any comment or opinion. 
The output must be only valid markdwon containing and the result must be the JSON of an object with one key named "summary" containing the summary for this book in strings. 
';
    }

    public function getPromptWithoutInstructions(): string
    {
        return $this->prompt;
    }

    #[\Override]
    public function convertResult(string $result): string
    {
        $result = preg_replace('/<think>.*?<\/think>/s', '', $result) ?? '';

        try {
            $result = trim($result, "´`\n\r\t\v\0");
            if (str_starts_with($result, 'json')) {
                $result = substr($result, 4);
            }
            $items = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }

        if (!is_array($items) || !isset($items['summary']) || !is_string($items['summary'])) {
            return $result;
        }

        return $items['summary'];
    }
}
