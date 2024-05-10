<?php

namespace App\Controller;

use App\Entity\Shelf;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShelfController extends AbstractController
{
    #[Route('/shelf/{slug}/{page}', name: 'app_shelf', requirements: ['page' => '\d+'])]
    public function index(Shelf $shelf, PaginatorInterface $paginator, int $page = 1): Response
    {
        $pagination = $paginator->paginate($shelf->getBooks(), $page, 60);

        return $this->render('shelf/index.html.twig', [
            'shelf' => $shelf,
            'pagination' => $pagination,
        ]);
    }
}
