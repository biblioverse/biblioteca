<?php

namespace App\Ai\Prompt;

interface BookPromptInterface
{
    public function getPrompt(): string;

    public function initialisePrompt(): void;

    public function convertResult(string $result): string|array;

    public function setPrompt(string $prompt): void;
}
