<?php

namespace App\Twig\Components;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use App\Search\QueryTokenizer;
use App\Search\TypesenseTokenHandler;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent(method: 'get')]
class Search
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public const PER_PAGE=18;

    #[LiveProp(writable: true)]
    public string $query = '';
    public array $books = [];
    public array $facets = [];
    public array $suggestions = [];
    public int $found = 0;

    #[LiveProp(writable: true)]
    public array $filters = [];

    #[LiveProp(writable: true, url: true)]
    public string $fullQuery = '';

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    public array $pagination = [];

    public function __construct(protected CollectionFinder $bookFinder, protected QueryTokenizer $lexer, protected TypesenseTokenHandler $tokenHandler)
    {
    }

    #[PostMount]
    public function postMount(): void
    {
        $tokens = $this->extractFilters(true);
        $this->getResults($tokens);
    }

    #[LiveAction]
    public function addToQuery(#[LiveArg] string $value): void
    {

        if(!str_contains($this->fullQuery, $value)){
            $this->query = $value;
        } else {
            foreach ($this->filters as $key => $filter) {
                if(str_contains($filter, $value)) {
                    unset($this->filters[$key]);
                }
            }
        }
        $tokens = $this->extractFilters();
        $this->getResults($tokens);
    }

    protected function extractFilters($initial=false):array
    {
        if(!$initial) {
            $this->fullQuery = implode(' ', $this->filters) . ' ' . $this->query;
            $this->page = 1;
        }

        $tokens = $this->lexer->tokenize($this->fullQuery);
        foreach ($tokens as $key => $token) {
            if($key !== 'TEXT') {
                foreach ($token as $value) {
                    $this->filters[$value] = $value;
                }
            }
        }
        if(($tokens['TEXT']??'') !== $this->query) {
            $this->query = $tokens['TEXT']??'';
        }
        return $tokens;
    }


    protected function getResults(array $tokens):void
    {
        $complexQuery = $this->tokenHandler->handle($tokens);

        $complexQuery->facetBy('authors,serie,tags, extension, verified');

        $complexQuery->perPage(self::PER_PAGE);
        $complexQuery->numTypos(2);
        $complexQuery->page($this->page);
        /** @var TypesenseResponse $result */
        $result = $this->bookFinder->query($complexQuery);
        $results = $result->getResults();
        $this->facets = $result->getFacetCounts();
        $this->found = $result->getFound();

        $pages = ceil($this->found / self::PER_PAGE);
        $this->pagination = [
            'page' => $this->page,
            'pages' => $pages,
            'perPage' => self::PER_PAGE,
            'total' => $this->found,
            'lastPage' => $pages ?? 1,
            'nextPage' => $this->page < $pages ? $this->page + 1 : null,
            'previousPage' => $this->page > 1 ? $this->page - 1 : null,
        ];




        foreach ($results as $resultItem) {
            $this->books[$resultItem->getId()]['book'] = $resultItem;
        }
    }


    public function __invoke(): void
    {
        $tokens = $this->extractFilters();



        $this->getResults($tokens);
    }
}
