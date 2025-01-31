<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookRepository;
use App\Service\BookInteractionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/authors')]
class AuthorController extends AbstractController
{
    #[Route('/{name}', name: 'app_author_detail')]
    public function detail(string $name, BookRepository $bookRepository, BookInteractionService $bookInteractionService): Response
    {
        $books = $bookRepository->findByAuthor($name);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Invalid user');
        }

        $series = [];
        $otherBooks = [];
        $tags = [];
        foreach ($books as $book) {
            if ($book->getSerie() !== null) {
                $series[$book->getSerie()] = $book->getSerie();
            } else {
                $otherBooks[] = $book;
            }

            if ($book->getTags() !== null) {
                foreach ($book->getTags() as $tag) {
                    if (!array_key_exists($tag, $tags)) {
                        $tags[$tag] = [];
                    }
                    $tags[$tag][] = $book;
                }
            }
        }

        $booksInSeries = [];
        foreach ($series as $serie) {
            $booksInSeries[$serie] = $bookRepository->getFirstUnreadBook($serie);
        }

        $stats = $bookInteractionService->getStats($books, $user);

        return $this->render('author/detail.html.twig', [
            'author' => $name,
            'books' => $books,
            'booksInSeries' => $booksInSeries,
            'otherBooks' => $otherBooks,
            'tags' => $tags,
            'readBooks' => $stats['readBooks'],
            'hiddenBooks' => $stats['hiddenBooks'],
            'inProgressBooks' => $stats['inProgressBooks'],
        ]);
    }
}
