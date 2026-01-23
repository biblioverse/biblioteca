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
    public function __construct(private readonly BookRepository $bookRepository, private readonly BookInteractionService $bookInteractionService)
    {
    }

    #[Route('/{name}', name: 'app_author_detail')]
    public function detail(string $name): Response
    {
        $books = $this->bookRepository->findByAuthor($name);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Invalid user');
        }

        $series = [];
        $otherBooks = [];
        $publishers = [];
        $ageCategories = [];
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

            if ($book->getPublisher() !== null) {
                $publishers[$book->getPublisher()][] = $book;
            }

            if ($book->getAgeCategory() !== null) {
                $ageCategories[$book->getAgeCategory()->label()][] = $book;
            }
        }

        $booksInSeries = [];
        foreach ($series as $serie) {
            $booksInSeries[$serie] = $this->bookRepository->getFirstUnreadBook($serie);
        }

        $stats = $this->bookInteractionService->getStats($books, $user);

        return $this->render('author/detail.html.twig', [
            'author' => $name,
            'books' => $books,
            'booksInSeries' => $booksInSeries,
            'otherBooks' => $otherBooks,
            'tags' => $tags,
            'ageCategories' => $ageCategories,
            'publishers' => $publishers,
            'readBooks' => $stats['readBooks'],
            'hiddenBooks' => $stats['hiddenBooks'],
            'inProgressBooks' => $stats['inProgressBooks'],
        ]);
    }
}
