<?php

namespace App\Ai\Prompt;

use App\Entity\Book;

interface BookPromptInterface
{
    public function getPrompt(): string;

    public function getBook(): Book;

    public function initialisePrompt(): void;

    public function convertResult(string $result): string|array;

    public function replaceBookOccurrence(string $prompt): string;

    public function setPrompt(string $prompt): void;
}
