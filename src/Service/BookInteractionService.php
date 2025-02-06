<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;

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
                if ($interaction->getReadStatus() === ReadStatus::Finished) {
                    $readBooks++;
                } elseif ($interaction->getReadStatus() === ReadStatus::Started) {
                    $inProgressBooks++;
                }

                if ($interaction->getReadingList() === ReadingList::Ignored) {
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
