<?php

namespace App\Controller;

use App\Service\BookSearch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request, BookSearch $bookSearch): Response
    {
        $term = $request->get('term', '');
        if (!is_string($term)) {
            return $this->redirectToRoute('app_dashboard');
        }

        $results = $bookSearch->autocomplete($term);

        return $this->render('search/index.html.twig', [
            'books' => $results,
            'term' => $term,
        ]);
    }
}
