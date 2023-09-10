<?php

namespace App\Controller;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Form\BookFilterType;
use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    use PageFilterFormTrait;

    #[Route('/{page}', name: 'app_homepage', requirements: ['page' => '\d+'])]
    public function index(Request $request, BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $qb = $bookRepository->getAllBooksQueryBuilder();

        $form = $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            18
        );

        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }
}
