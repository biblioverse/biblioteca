<?php

namespace App\Twig\Components;

use App\Entity\Shelf;
use App\Entity\User;
use App\Service\SearchHelper;
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

#[AsLiveComponent(method: 'get')]
class Search
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public const PER_PAGE = 16;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';
    #[LiveProp(writable: true, url: true)]
    public string $filterQuery = '';
    public array $filterFields = [
        'id',
        'title',
        'serie',
        'summary',
        'serieIndex',
        'extension',
        'authors',
        'verified',
        'tags',
        'age',
        'read',
        'hidden',
        'favorite',
    ];

    public ?string $filterQueryError = null;
    #[LiveProp(writable: true, url: true)]
    public string $orderQuery = 'updated:desc';

    public array $books = [];
    public array $facets = [];

    public int $found = 0;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public string $shelfname = '';

    #[LiveProp(writable: true)]
    public bool $advanced = false;

    public array $pagination = [];

    public function __construct(protected RequestStack $requestStack, protected SearchHelper $searchHelper, protected Security $security, protected EntityManagerInterface $manager)
    {
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
            $this->searchHelper->query->filterBy('');
            $this->filterQueryError = $e->getMessage();
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
