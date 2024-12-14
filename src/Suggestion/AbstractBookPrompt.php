<?php

namespace App\Suggestion;

use App\Entity\Book;
use App\Entity\User;

abstract class AbstractBookPrompt
{
    protected function replaceBookOccurrence(Book $book, string $prompt): string
    {
        $bookString = '"'.$book->getTitle().'" by '.implode(' and ', $book->getAuthors());

        if ($book->getSerie() !== null) {
            $bookString .= ' number '.$book->getSerieIndex().' in the series "'.$book->getSerie().'"';
        }

        return str_replace('{book}', $bookString, $prompt);
    }

    abstract public function getPrompt(Book $book, ?User $user): string;
}
