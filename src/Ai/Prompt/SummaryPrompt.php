<?php

namespace App\Ai\Prompt;

use App\Entity\User;

class SummaryPrompt extends AbstractBookPrompt
{
    #[\Override]
    public function initialisePrompt(): void
    {
        $prompt = $this->config->resolve('AI_SUMMARY_PROMPT');

        if ($this->user instanceof User) {
            $prompt = $this->user->getBookSummaryPrompt() ?? $prompt;
        }

        $prompt .= ' Remember to keep it short and concise. Do not add any comment or opinion. The output must be only valid JSON format. 
It must be an object with one key named "summary" containing the summary for this book in strings. 
Do not add anything else than json. Do not add any other text or comment.';

        $this->prompt = $this->replaceBookOccurrence($prompt);
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
