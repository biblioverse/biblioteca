<?php

namespace App\Controller;

use App\Entity\Shelf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShelfController extends AbstractController
{
    #[Route('/shelf/{slug}', name: 'app_shelf')]
    public function index(Shelf $shelf): Response
    {

        return $this->render('shelf/index.html.twig', [
            'shelf' => $shelf,
            'books' => $shelf->getBooks(),
        ]);
    }
}
