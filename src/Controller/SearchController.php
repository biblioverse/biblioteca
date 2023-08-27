<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search/{query}', name: 'app_search')]
    public function index( BookRepository $bookRepository, PaginatorInterface $paginator, ?string $query = null,int $page=1): Response
    {
        if($query===null){
            $books=[];
        } else {
            $books = $bookRepository->search($query, 500);
        }


        return $this->render('search/index.html.twig', [
            'query' => $query,
            'pagination' => $paginator->paginate($books, $page,12),
        ]);
    }
}
