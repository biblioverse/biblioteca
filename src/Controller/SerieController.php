<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/series')]
class SerieController extends AbstractController
{

    #[Route('/{name}', name: 'app_serie_detail')]
    public function detail(string $name, BookRepository $bookRepository): Response
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

        foreach ($books as $book) {
            foreach ($book->getAuthors() as $author) {
                $authors[$author] = $author;
            }
        }


        return $this->render('serie/detail.html.twig', [
            'serie' => $name,
            'books' => $books,
            'authors' => $authors,
            'firstUnreadBook' => $firstUnreadBook
        ]);
    }
}
