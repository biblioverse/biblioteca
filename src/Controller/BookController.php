<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/{authorSlug}/{book}/{slug}', name: 'app_book')]
    public function index(string $authorSlug, Book $book, string $slug): Response
    {
        if($authorSlug!==$book->getAuthorSlug()||$slug!==$book->getSlug()){
            return $this->redirectToRoute('app_book', [
                'authorSlug' => $book->getAuthorSlug(),
                'book'=> $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        return $this->render('book/index.html.twig', [
            'book' => $book,
        ]);
    }
}
