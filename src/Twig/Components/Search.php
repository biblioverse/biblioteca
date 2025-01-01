<?php

namespace App\Twig\Components;

use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use App\Entity\Shelf;
use App\Entity\User;
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

    public function __construct(protected RequestStack $requestStack, protected CollectionFinder $bookFinder, protected Security $security, protected EntityManagerInterface $manager)
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
        $complexQuery = new TypesenseQuery($this->query, 'title,serie,extension,authors,tags,summary');

        $complexQuery->sortBy($this->orderQuery);
        $complexQuery->filterBy($this->filterQuery);

        $complexQuery->facetBy('authors,serie,tags');

        $complexQuery->perPage(self::PER_PAGE);
        $complexQuery->numTypos(2);
        $complexQuery->page($this->page);

        try {
            /** @var TypesenseResponse $result */
            $result = $this->bookFinder->query($complexQuery);
            $this->filterQueryError = null;
        } catch (\Throwable $e) {
            $complexQuery->filterBy('');
            $this->filterQueryError = $e->getMessage();
            $result = $this->bookFinder->query($complexQuery);
        }
        $results = $result->getResults();
        $this->facets = $result->getFacetCounts();

        $this->found = $result->getFound();

        $pages = ceil($this->found / self::PER_PAGE);
        $this->pagination = [
            'page' => $this->page,
            'pages' => $pages,
            'perPage' => self::PER_PAGE,
            'total' => $this->found,
            'lastPage' => $pages,
            'nextPage' => $this->page < $pages ? $this->page + 1 : null,
            'previousPage' => $this->page > 1 ? $this->page - 1 : null,
        ];

        foreach ($results as $resultItem) {
            $this->books[$resultItem->getId()]['book'] = $resultItem;
        }
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
