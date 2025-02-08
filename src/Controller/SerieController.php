<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\ShelfRepository;
use App\Service\BookInteractionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/series')]
class SerieController extends AbstractController
{
    #[Route('/{name}', name: 'app_serie_detail', requirements: ['name' => '.+'])]
    public function detail(string $name, BookRepository $bookRepository, BookInteractionService $bookInteractionService, ShelfRepository $shelfRepository): Response
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

        $tags = [];
        $publishers = [];
        $ageCategories = [];
        $serie = [];
        $serieMax = 0;
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

            if ($book->getPublisher() !== null) {
                $publishers[$book->getPublisher()][] = $book;
            }

            if ($book->getAgeCategory() !== null) {
                $ageCategories[$book->getAgeCategory()->label()][] = $book;
            }

            $index = $book->getSerieIndex();
            if ($index === 0.0 || floor($index ?? 0.0) !== $index) {
                $index = '?';
            }
            $serie[$index] ??= [];
            $serie[$index][] = $book;
        }

        $keys = array_filter(array_keys($serie), static fn ($key) => is_numeric($key));
        if ($keys !== []) {
            $serieMax = max($keys);
        }

        $stats = $bookInteractionService->getStats($books, $user);

        return $this->render('serie/detail.html.twig', [
            'serie' => $name,
            'shelves' => $shelfRepository->findManualShelvesForUser($user),
            'books' => $serie,
            'serieMax' => $serieMax,
            'authors' => $authors,
            'firstUnreadBook' => $firstUnreadBook,
            'tags' => $tags,
            'ageCategories' => $ageCategories,
            'publishers' => $publishers,
            'readBooks' => $stats['readBooks'],
            'hiddenBooks' => $stats['hiddenBooks'],
            'inProgressBooks' => $stats['inProgressBooks'],
        ]);
    }
}
