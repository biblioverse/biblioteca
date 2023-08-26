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
}
