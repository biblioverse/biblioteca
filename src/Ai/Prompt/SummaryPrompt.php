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

        $prompt .= ' Remember to keep it short and concise. Do not add any comment or opinion, only the summary must be returned.';

        $this->prompt = $this->replaceBookOccurrence($prompt);
    }

    #[\Override]
    public function convertResult(string $result): string
    {
        return preg_replace('/<think>.*?<\/think>/s', '', $result) ?? '';
    }
}
