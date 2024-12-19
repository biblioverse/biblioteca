<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/authors')]
class AuthorController extends AbstractController
{

    #[Route('/{name}', name: 'app_author_detail')]
    public function detail(string $name, BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findByAuthor($name);

        $series = [];
        $otherBooks = [];
        foreach ($books as $book) {
            if ($book->getSerie() !== null) {
                $series[$book->getSerie()] = $book->getSerie();
            } else {
                $otherBooks[] = $book;
            }
        }

        $firstBooks = [];
        foreach ($series as $serie) {
            $firstUnreadBook = $bookRepository->getFirstUnreadBook($serie);
            $firstBooks[$serie] = $firstUnreadBook;
        }

        return $this->render('author/detail.html.twig', [
            'author' => $name,
            'firstBooks' => $firstBooks,
            'otherBooks' => $otherBooks,
        ]);
    }
}
