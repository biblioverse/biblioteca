<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/authors')]

class AuthorController extends AbstractController
{
    #[Route('/{page}', name: 'app_authors', requirements: ['page' => '\d+'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $authors = $bookRepository->getAllAuthors();

        $pagination = $paginator->paginate($authors,$page,300);

        return $this->render('author/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{slug}/{page}', name: 'app_author_detail', requirements: ['page' => '\d+'])]
    public function detail(string $slug, BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $authors = $bookRepository->getAllAuthors();
        $author = array_filter($authors, fn($serie) => $serie['authorSlug'] === $slug);

        $author= current($author);


        $pagination = $paginator->paginate(
            $bookRepository->getByAuthorQuery($slug),
            $page,
            18
        );

        return $this->render('author/detail.html.twig', [
            'pagination' => $pagination,
            'author' =>$author
        ]);
    }
}
