<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\ShelfRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/series')]
class SerieController extends AbstractController
{
    #[Route('/{name}', name: 'app_serie_detail')]
    public function detail(string $name, BookRepository $bookRepository, ShelfRepository $shelfRepository): Response
    {
        $books = $bookRepository->findBySerie($name);

        if ($books === []) {
            throw $this->createNotFoundException('No books found for this serie');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Invalid user');
        }

        $authors = [];

        $firstUnreadBook = $bookRepository->getFirstUnreadBook($name);

        $readBooks = 0;
        $hiddenBooks = 0;
        $inProgressBooks = 0;
        $tags = [];
        $bookData = [];
        foreach ($books as $book) {
            foreach ($book->getAuthors() as $author) {
                $authors[$author] = $author;
            }

            if ($book->getTags() !== null) {
                foreach ($book->getTags() as $tag) {
                    if (!array_key_exists($tag, $tags)) {
                        $tags[$tag] = [];
                    }
                    $tags[$tag][] = $book;
                }
            }

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

            $bookData[] = [
                'book' => $book,
                'interaction' => $interaction,
            ];
        }

        return $this->render('serie/detail.html.twig', [
            'serie' => $name,
            'shelves' => $shelfRepository->findManualShelvesForUser($user),
            'books' => $bookData,
            'authors' => $authors,
            'firstUnreadBook' => $firstUnreadBook,
            'tags' => $tags,
            'readBooks' => $readBooks,
            'hiddenBooks' => $hiddenBooks,
            'inProgressBooks' => $inProgressBooks,
        ]);
    }
}
