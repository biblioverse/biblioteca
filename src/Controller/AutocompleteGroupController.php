<?php

namespace App\Controller;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Search\QueryTokenizer;
use App\Search\TypesenseTokenHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @phpstan-type GroupType array{ item:string, slug:string, bookCount:int, booksFinished:int, lastBookIndex:int }
 */
class AutocompleteGroupController extends AbstractController
{

    public function __construct(private readonly CollectionFinder $bookFinder)
    {
    }

    #[Route('/autocomplete/group/{type}', name: 'app_autocomplete_group')]
    public function index(Request $request, BookRepository $bookRepository, string $type): Response
    {
        $query = $request->get('query');
        if (!is_string($query)) {
            return new JsonResponse(['results' => []]);
        }

        $json = ['results' => []];

        if ($type === 'ageCategory') {
            foreach (User::AGE_CATEGORIES as $ageCategory => $ageCategoryId) {
                $json['results'][] = ['value' => $ageCategoryId, 'text' => $ageCategory];
            }

            return new JsonResponse($json);
        }

        /** @var array<GroupType> $group */
        $group = match ($type) {
            'serie' => $bookRepository->getAllSeries()->getResult(),
            'authors' => $bookRepository->getAllAuthors(),
            'tags' => $bookRepository->getAllTags(),
            'publisher' => $bookRepository->getAllPublishers()->getResult(),
            default => [],
        };

        $exactmatch = false;

        if ($type !== 'authors' && $query === '' && $request->get('create', true) !== true) {
            $json['results'][] = ['value' => 'no_'.$type, 'text' => '[No '.$type.' defined]'];
        }
        foreach ($group as $item) {
            if (!str_contains(strtolower($item['item']), strtolower($query))) {
                continue;
            }
            if (strtolower($item['item']) === strtolower($query)) {
                $exactmatch = true;
            }
            $json['results'][] = ['value' => $item['item'], 'text' => $item['item']];
        }

        if (!$exactmatch && strlen($query) > 2 && $request->get('create', true) === true) {
            $json['results'][] = ['value' => $query, 'text' => 'New: '.$query];
        }

        return new JsonResponse($json);
    }
    #[Route('/autocomplete/search/lexer', name: 'app_autocomplete_search')]
    public function search(Request $request, QueryTokenizer $lexer, TypesenseTokenHandler $tokenHandler): Response
    {
        $query = trim($request->get('query',''));

        $json = ['results' => []];

        $tokens = $lexer->tokenize($query);
        $complexQuery = $tokenHandler->handle($tokens);

        $complexQuery->facetBy('authors,serie,tags');

        $complexQuery->perPage(16);
        $complexQuery->numTypos(2);

        $facets = $this->bookFinder->query($complexQuery)->getFacetCounts();


        if($query!=='' ) {
            $json['results'][] = ['value' => $query, 'text' => $query, 'group_by'=>['text']];
            $json['results']['optgroups'][] = ['label' => 'Text search', 'value' => 'text'];

        }

        foreach ($facets as $facet) {
            $type = $facet['field_name'];
            foreach ($facet['counts'] as $item) {
                $json['results']['options'][] = ['group_by'=>[$type], 'value' => $type.':"'.$item['value'].'"', 'text' => $item['value']. ' ('.$item['count'].')'];
                $json['results']['options'][] = ['group_by'=>['no-'.$type], 'value' => $type.'-:"'.$item['value'].'"', 'text' => 'Exclude '.$item['value']. ' ('.$item['count'].')'];
            }
            $json['results']['optgroups'][] = ['label' => 'Filter by '.ucfirst($type), 'value' => $type];
            $json['results']['optgroups'][] = ['label' => 'Exclude '.ucfirst($type), 'value' => 'no-'.$type];
        }

        return new JsonResponse($json);
    }
}
