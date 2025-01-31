<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\User;

class BookInteractionService
{
    /**
     * @param Book[] $books
     * @return array{readBooks: int, hiddenBooks: int, inProgressBooks: int}
     */
    public function getStats($books, User $user): array
    {
        $readBooks = 0;
        $hiddenBooks = 0;
        $inProgressBooks = 0;
        foreach ($books as $book) {
            $interaction = $book->getLastInteraction($user);
            if ($interaction !== null) {
                if ($interaction->isFinished()) {
                    $readBooks++;
                } elseif ($interaction->getReadPages() > 0) {
                    $inProgressBooks++;
                }

                if ($interaction->isHidden()) {
                    $hiddenBooks++;
                }
            }
        }

        return [
            'readBooks' => $readBooks,
            'hiddenBooks' => $hiddenBooks,
            'inProgressBooks' => $inProgressBooks,
        ];
    }
}
