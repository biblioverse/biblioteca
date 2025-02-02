<?php

namespace App\Controller\OPDS;

use App\Entity\OpdsAccess;
use App\Entity\Shelf;
use App\Entity\User;
use App\OPDS\Opds;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Repository\ShelfRepository;
use App\Service\Search\SearchHelper;
use App\Service\ShelfManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/opds/{accessKey}', name: 'opds_')]
class OpdsController extends AbstractController
{
    public function __construct(RequestStack $requestStack, private readonly Opds $opds, private readonly BookRepository $bookRepository, private readonly SearchHelper $searchHelper)
    {
        $current = $requestStack->getCurrentRequest();
        if (!$current instanceof Request) {
            throw new \RuntimeException('No current request');
        }
        $this->opds->setAccessKey($current->attributes->getString('accessKey'));
    }

    private function generateOpdsUrl(string $route, array $params = []): string
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Invalid user');
        }

        $accesses = $user->getOpdsAccesses()->toArray();
        if (count($accesses) === 0) {
            throw $this->createAccessDeniedException('Invalid user, no opds access');
        }
        /** @var OpdsAccess $opdsAccess */
        $opdsAccess = reset($accesses);

        $params['accessKey'] ??= $opdsAccess->getToken();

        return $this->generateUrl($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    #[Route('/', name: 'start')]
    public function index(): Response
    {
        $opds = $this->opds->getOpdsConfig();
        $feeds = [];
        $feeds[] = $this->opds->getNavigationEntry('books:series', 'Series', $this->generateOpdsUrl('opds_group', ['type' => 'series']));
        $feeds[] = $this->opds->getNavigationEntry('books:authors', 'Authors', $this->generateOpdsUrl('opds_group', ['type' => 'authors']));
        $feeds[] = $this->opds->getNavigationEntry('books:tags', 'Tags', $this->generateOpdsUrl('opds_group', ['type' => 'tags']));
        $feeds[] = $this->opds->getNavigationEntry('books:shelves', 'Shelves', $this->generateOpdsUrl('opds_shelves', []));
        $feeds[] = $this->opds->getNavigationEntry('books:reading-list', 'My Reading List', $this->generateOpdsUrl('opds_readinglist', []));
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $opds = $this->opds->getOpdsConfig()->isSearch();
        $opds->title('Search');
        $q = $request->query->getString('q', $request->request->getString('q', $request->attributes->getString('q')));
        if ($q === '') {
            $q = $request->query->getString('query', $request->request->getString('query', $request->attributes->getString('query')));
        }
        $this->searchHelper->prepareQuery($q, perPage: 200)->execute();
        $books = $this->searchHelper->getBooks();

        $feeds = [];
        foreach ($books as $result) {
            $feeds[] = $this->opds->convertBookToOpdsEntry($result);
        }

        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/group/{type}', name: 'group')]
    public function group(string $type): Response
    {
        $group = match ($type) {
            'authors' => $this->bookRepository->getAllAuthors(),
            'tags' => $this->bookRepository->getAllTags(),
            'series' => $this->bookRepository->getAllSeries(),
            default => throw $this->createAccessDeniedException('Invalid group type'),
        };

        $opds = $this->opds->getOpdsConfig();
        $opds->title(ucfirst($type));
        $feeds = [];
        foreach ($group as $item) {
            $feeds[] = $this->opds->getNavigationEntry('group:'.$type.':'.$item['item'], $item['item'], $this->generateOpdsUrl('opds_group_item', ['type' => $type, 'item' => $item['item']]));
        }
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/shelves/', name: 'shelves')]
    public function shelves(ShelfRepository $shelfRepository): Response
    {
        $opds = $this->opds->getOpdsConfig();
        $opds->title('Shelves');

        $shelves = $shelfRepository->findBy(['user' => $this->getUser()]);

        $feeds = [];
        foreach ($shelves as $item) {
            $feeds[] = $this->opds->getNavigationEntry('shelf:'.$item->getId(), $item->getName(), $this->generateOpdsUrl('opds_shelf_item', ['item' => $item->getId()]));
        }
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/shelves/{item}', name: 'shelf_item')]
    public function shelfItem(Shelf $item, ShelfManager $manager): Response
    {
        $opds = $this->opds->getOpdsConfig();
        $opds->title('Shelf: '.$item->getName());

        $books = $manager->getBooksInShelf($item);

        $feeds = [];
        foreach ($books as $book) {
            $feeds[] = $this->opds->convertBookToOpdsEntry($book);
        }
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/reading-list', name: 'readinglist')]
    public function readinglist(BookInteractionRepository $bookInteractionRepository): Response
    {
        $opds = $this->opds->getOpdsConfig();
        $opds->title('Reading List');

        $books = $bookInteractionRepository->getFavourite();

        $feeds = [];
        foreach ($books as $bookInteraction) {
            if ($bookInteraction->getBook() === null) {
                continue;
            }
            $feeds[] = $this->opds->convertBookToOpdsEntry($bookInteraction->getBook());
        }
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }

    #[Route('/group/{type}/{item}', name: 'group_item', requirements: ['item' => '.+'])]
    public function groupItem(string $type, string $item): Response
    {
        $group = match ($type) {
            'authors' => $this->bookRepository->findByAuthor($item),
            'tags' => $this->bookRepository->findByTag($item),
            'series' => $this->bookRepository->findBy(['serie' => $item]),
            default => throw $this->createAccessDeniedException('Invalid group type'),
        };

        $opds = $this->opds->getOpdsConfig();

        $opds->title($item);

        $feeds = [];
        foreach ($group as $book) {
            $feeds[] = $this->opds->convertBookToOpdsEntry($book);
        }
        $opds->feeds($feeds);

        return $this->opds->convertOpdsResponse($opds->get()->getResponse());
    }
}
