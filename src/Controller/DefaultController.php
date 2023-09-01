<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/{page}', name: 'app_homepage', requirements: ['page' => '\d+'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $pagination = $paginator->paginate(
            $bookRepository->getAllBooksQuery(),
            $page,
            18
        );
        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    #[Route('/favorites/{page}', name: 'app_favorites', requirements: ['page' => '\d+'])]
    public function favorites(BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $pagination = $paginator->paginate(
            $bookRepository->getFavoriteBooksQuery(),
            $page,
            18
        );
        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/finished/{read}/{page}', name: 'app_read', requirements: ['page' => '\d+'])]
    public function finished(BookRepository $bookRepository, PaginatorInterface $paginator, int $read, int $page=1): Response
    {
        $pagination = $paginator->paginate(
            $bookRepository->getBooksByReadStatus((bool)$read),
            $page,
            18
        );
        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/unverified/{page}', name: 'app_unverified', requirements: ['page' => '\d+'])]
    public function unverified(BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $pagination = $paginator->paginate(
            $bookRepository->getUnverifiedBooksQuery(),
            $page,
            18
        );
        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

}
