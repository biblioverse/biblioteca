<?php

namespace App\Controller;

use App\Entity\Shelf;
use App\Service\ShelfManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShelfController extends AbstractController
{
    public function __construct(private readonly ShelfManager $shelfManager)
    {
    }

    #[Route('/shelf/{slug:shelf}', name: 'app_shelf')]
    public function index(Shelf $shelf): Response
    {
        $books = $this->shelfManager->getBooksInShelf($shelf);

        return $this->render('shelf/index.html.twig', [
            'shelf' => $shelf,
            'books' => $books,
        ]);
    }
}
