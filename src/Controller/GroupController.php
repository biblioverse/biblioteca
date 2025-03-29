<?php

namespace App\Controller;

use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Search\Search;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/groups')]
class GroupController extends AbstractController
{
    #[Route('/{type}/{letter}', name: 'app_groups')]
    public function groups(Search $searchService, string $type, string $letter = 'a'): Response
    {
        $searchQuery = new SearchQuery(
            q: '',
            queryBy: 'name',
            sortBy: '',
            maxFacetValues: 35,
            numTypos: 2,
            facetBy: 'first_letter(sort_by: _alpha:asc)',
        );
        $results = $searchService->search($type, $searchQuery);

        $facets = $results->getFacetCounts();
        $facets = $facets[0]['counts'];
        $results = [];
        $page = 1;
        do {
            $searchQuery = new SearchQuery(
                q: '',
                queryBy: 'name',
                filterBy: 'first_letter:='.strtoupper($letter),
                sortBy: 'sortable_id:asc',
                limit: 100,
                numTypos: 2,
                page: $page,
            );
            $newresults = $searchService->search($type, $searchQuery)->getResults();
            $page++;

            $results = array_merge($results, $newresults);
        } while (count($newresults) > 0);

        return $this->render('group/index.html.twig', [
            'facets' => $facets,
            'results' => $results,
            'letter' => $letter,
            'type' => $type,
        ]);
    }
}
