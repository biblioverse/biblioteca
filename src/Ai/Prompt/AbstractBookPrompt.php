<?php

namespace App\Ai\Prompt;

use App\Config\ConfigValue;
use App\Entity\Book;

abstract class AbstractBookPrompt implements BookPromptInterface
{
    protected string $prompt;

    public function __construct(
        protected Book $book,
        protected ConfigValue $config,
        protected string $language,
    ) {
    }

    #[\Override]
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    #[\Override]
    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    #[\Override]
    public function getBook(): Book
    {
        return $this->book;
    }

    #[\Override]
    public function replaceBookOccurrence(string $prompt): string
    {
        $bookString = $this->book->getPromptString();

        $language = $this->book->getLanguage() ?? $this->language;

        return str_replace(['{book}', '{language}'], [$bookString, $this->getFullLanguageName($language)], $prompt);
    }

    private function getFullLanguageName(string $language): string
    {
        if (strlen($language) !== 2 || !class_exists('\Locale')) {
            return $language;
        }

        return \Locale::getDisplayLanguage($language);
    }
}
