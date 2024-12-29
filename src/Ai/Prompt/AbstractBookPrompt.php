<?php

namespace App\Ai\Prompt;

use App\Entity\Book;
use App\Entity\User;

abstract class AbstractBookPrompt implements BookPromptInterface
{
    protected string $prompt;

    public function __construct(protected Book $book, protected ?User $user)
    {
        $this->initialisePrompt();
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

    public function getBook(): Book
    {
        return $this->book;
    }

    public function replaceBookOccurrence(string $prompt): string
    {
        $bookString = '"'.$this->book->getTitle().'" by '.implode(' and ', $this->book->getAuthors());

        if ($this->book->getSerie() !== null) {
            $bookString .= ' number '.$this->book->getSerieIndex().' in the series "'.$this->book->getSerie().'"';
        }

        return str_replace('{book}', $bookString, $prompt);
    }
}
