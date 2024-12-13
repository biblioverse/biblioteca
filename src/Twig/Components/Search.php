<?php

namespace App\Twig\Components;

use App\Entity\Shelf;
use App\Entity\User;
use App\Service\Search\SearchHelper;
use Biblioverse\TypesenseBundle\Exception\SearchException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Typesense\Exceptions\RequestMalformed;

#[AsLiveComponent(method: 'get')]
class Search
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';
    #[LiveProp(writable: true, url: true)]
    public string $filterQuery = '';
    public array $filterFields;

    public ?string $filterQueryError = null;
    #[LiveProp(writable: true, url: true)]
    public string $orderQuery = 'updated:desc';

    public array $books = [];
    public array $facets = [];

    public ?array $hint = null;
    public int $found = 0;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public string $shelfname = '';

    #[LiveProp(writable: true)]
    public bool $advanced = false;

    public array $pagination = [];

    public function __construct(
        protected RequestStack $requestStack,
        protected SearchHelper $searchHelper,
        protected Security $security,
        protected EntityManagerInterface $manager,
    ) {
        $this->filterFields = $this->searchHelper->getBookFieldsForQuery();
    }

    #[PostMount]
    public function postMount(): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest instanceof Request) {
            return;
        }
        if ($currentRequest->getPathInfo() === '/all') {
            $this->getResults();
        }
    }

    #[LiveAction]
    public function addFilter(#[LiveArg] string $value): void
    {
        if (!str_contains($this->filterQuery, $value)) {
            if ($this->filterQuery !== '') {
                $this->filterQuery .= ' && ';
            }
            $this->filterQuery .= $value;
        } else {
            $this->filterQuery = str_replace($value, '', $this->filterQuery);
        }
        $this->getResults();
    }

    #[LiveAction]
    public function hint(): void
    {
        $this->getResults();
        $this->searchHelper->maxFacetValues = 50;
        $this->searchHelper->execute();
        $hints = $this->hint = $this->searchHelper->getQueryHints();
        if ($hints === null) {
            return;
        }
        if (array_key_exists('filter_by', $hints)) {
            $this->filterQuery = $hints['filter_by'];
            $this->query = '';
        }
        $this->searchHelper->maxFacetValues = 10;

        $this->getResults();
    }

    #[LiveAction]
    public function replaceOrderBy(#[LiveArg] string $value): void
    {
        $this->orderQuery = $value;
        $this->getResults();
    }

    protected function getResults(): void
    {
        $this->searchHelper->prepareQuery($this->query, $this->filterQuery, $this->orderQuery, page: $this->page);

        try {
            $this->searchHelper->execute();
            $this->filterQueryError = null;
        } catch (\Throwable $e) {
            $this->filterQueryError = $e->getMessage();
            // In case of filter-query error, remove "filterBy" and try again
            if ($e instanceof SearchException && $e->getPrevious() instanceof RequestMalformed) {
                $this->filterQueryError = $e->getMessage();
                $this->searchHelper->prepareQuery($this->query, '', $this->orderQuery, page: $this->page);
                $this->searchHelper->execute();
            }
            $this->searchHelper->execute();
        }

        $this->books = $this->searchHelper->getBooks();
        $this->facets = $this->searchHelper->getFacets();

        $this->pagination = $this->searchHelper->getPagination();
        $this->found = $this->pagination['total'];
    }

    public function __invoke(): void
    {
        $this->getResults();
    }

    #[LiveAction]
    public function save(): void
    {
        $sf = new Shelf();
        $sf->setName($this->shelfname);
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be logged in');
        }
        $sf->setUser($user);
        $sf->setQueryString($this->query);
        $sf->setQueryFilter($this->filterQuery);
        $sf->setQueryOrder($this->orderQuery);
        $this->manager->persist($sf);
        $this->manager->flush();
        $this->getResults();
        $this->dispatchBrowserEvent('manager:flush');
    }
}
