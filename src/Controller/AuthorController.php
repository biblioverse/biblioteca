<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/authors')]
class AuthorController extends AbstractController
{
    #[Route('/{page}', name: 'app_authors', requirements: ['page' => '\d+'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $authors = $bookRepository->getAllAuthors();

        $pagination = $paginator->paginate($authors, $page, 18);

        return $this->render('group/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'type' => 'authors',
        ]);
    }

    #[Route('/{slug}/{page}', name: 'app_authors_detail', requirements: ['page' => '\d+'])]
    public function detail(string $slug, BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $authors = $bookRepository->getAllAuthors();

        $author = $authors[$slug] ?? null;

        if (null === $author) {
            return $this->redirectToRoute('app_authors');
        }

        $pagination = $paginator->paginate(
            $bookRepository->getByAuthorQuery($author['item']),
            $page,
            18
        );

        return $this->render('author/detail.html.twig', [
            'pagination' => $pagination,
            'author' => $author,
        ]);
    }
}
