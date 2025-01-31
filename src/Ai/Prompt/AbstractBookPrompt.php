<?php

namespace App\Ai\Prompt;

use App\Config\ConfigValue;
use App\Entity\Book;
use App\Entity\User;

abstract class AbstractBookPrompt implements BookPromptInterface
{
    protected string $prompt;

    public function __construct(
        protected Book $book,
        protected ?User $user,
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
        $title = $this->book->getTitle();
        if (preg_match('/T\d+/', $title) !== false) {
            $bookString = '"'.$this->book->getTitle().'" by '.implode(' and ', $this->book->getAuthors());
        } else {
            $bookString = ' by '.implode(' and ', $this->book->getAuthors());
        }

        if ($this->book->getSerie() !== null) {
            $bookString .= ' number '.$this->book->getSerieIndex().' in the series "'.$this->book->getSerie().'"';
        }

        $language = $this->book->getLanguage() ?? $this->language;

        $prompt = str_replace('{book}', $bookString, $prompt);

        return str_replace('{language}', $this->getFullLanguageName($language), $prompt);
    }

    private function getFullLanguageName(string $language): string
    {
        if (strlen($language) !== 2 || !class_exists('\Locale')) {
            return $language;
        }

        return \Locale::getDisplayLanguage($language);
    }
}
