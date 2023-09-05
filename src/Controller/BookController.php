<?php

namespace App\Controller;

use App\Entity\Book;
use App\Service\BookFileSystemManager;
use App\Service\BookSuggestions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/{authorSlug}/{book}/{slug}', name: 'app_book')]
    public function index(string $authorSlug, Book $book, string $slug, BookSuggestions $bookSuggestions): Response
    {
        if ($authorSlug !== $book->getAuthorSlug() || $slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'authorSlug' => $book->getAuthorSlug(),
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $suggestions = $bookSuggestions->getSuggestions($book);

        return $this->render('book/index.html.twig', [
            'book' => $book,
            'suggestions' => $suggestions,
        ]);
    }

    #[Route('/{id}/{image}', name: 'app_book_downloadImage')]
    public function downloadImage(Book $book, string $image, BookSuggestions $bookSuggestions, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        $suggestions = $bookSuggestions->getSuggestions($book);

        $url = $suggestions['image'][$image] ?? null;

        if ($url === null) {
            throw $this->createNotFoundException('Image not found');
        }

        $book = $fileSystemManager->downloadBookCover($book, $url);

        $entityManager->flush();

        return $this->redirectToRoute('app_book', [
            'authorSlug' => $book->getAuthorSlug(),
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ], 301);
    }
}
