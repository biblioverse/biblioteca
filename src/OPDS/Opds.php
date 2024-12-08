<?php

namespace App\OPDS;

use ACSEO\TypesenseBundle\Finder\SpecificCollectionFinder;
use App\Entity\Book;
use App\Service\BookFileSystemManager;
use Kiwilan\Opds\Entries\OpdsEntryBook;
use Kiwilan\Opds\Entries\OpdsEntryBookAuthor;
use Kiwilan\Opds\Entries\OpdsEntryNavigation;
use Kiwilan\Opds\Opds as KiwilanOpds;
use Kiwilan\Opds\OpdsConfig;
use Kiwilan\Opds\OpdsResponse;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Opds
{
    protected const TITLE = 'Biblioteca';

    protected string $currentAccessKey;

    public function __construct(private RouterInterface $router, private CacheManager $imagineCacheManager, protected SpecificCollectionFinder $bookFinder, private BookFileSystemManager $bookFileSystemManager)
    {
    }

    public function setCurrentAccessKey(string $accessKey): void
    {
        $this->currentAccessKey = $accessKey;
    }

    protected function getOpdsInit(): KiwilanOpds
    {
        return KiwilanOpds::make(new OpdsConfig(
            name: self::TITLE,
            author: 'Biblioteca',
            authorUrl: $this->router->generate('app_dashboard', ['accessKey'=>$this->currentAccessKey], referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            startUrl: $this->router->generate('opds_start', ['accessKey'=>$this->currentAccessKey], referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            searchUrl: $this->router->generate('opds_search', ['accessKey'=>$this->currentAccessKey], referenceType: UrlGeneratorInterface::ABSOLUTE_URL), // Search URL, will be included in top navigation
            paginationQuery: 'page', // query parameter for pagination
            updated: new \DateTime(), // Last update of OPDS feed
            maxItemsPerPage: 16, // Max items per page, default is 16
        ))
        ->title(self::TITLE);
    }

    protected function convertOpdsResponse(OpdsResponse $response): Response
    {
        $symfonyResponse = new Response();
        $symfonyResponse->setContent($response->getContents());
        foreach ($response->getHeaders() as $key => $value) {
            $symfonyResponse->headers->set($key, $value);
        }

        return $symfonyResponse;
    }

    public function getStartPage(): Response
    {
        $opds = $this->getOpdsInit();

        $opds->feeds([
            new OpdsEntryNavigation('books:series', 'Series', $this->router->generate('opds_group',  ['accessKey'=>$this->currentAccessKey, 'type' => 'serie'], referenceType: UrlGeneratorInterface::ABSOLUTE_URL)),
            new OpdsEntryNavigation('books:authors', 'Authors', $this->router->generate('opds_group', ['accessKey'=>$this->currentAccessKey, 'type' => 'authors'], referenceType: UrlGeneratorInterface::ABSOLUTE_URL)),
            new OpdsEntryNavigation('books:tags', 'Tags', $this->router->generate('opds_group', ['accessKey'=>$this->currentAccessKey, 'type' => 'tags'], referenceType: UrlGeneratorInterface::ABSOLUTE_URL)),
        ]);

        return $this->convertOpdsResponse($opds->get()->getResponse());
    }

    public function getSearchPage(string $query, int $page = 1): Response
    {
        $opds = $this->getOpdsInit()->isSearch();
        $opds->title('Search - '.self::TITLE);

        $books = $this->bookFinder->search($query)->getResults();

        $feeds = [];
        foreach ($books as $result) {
            $feeds[] = $this->convertBookToOpdsEntry($result);
        }

        $opds->feeds($feeds);

        return $this->convertOpdsResponse($opds->get()->getResponse());
    }

    public function getGroupPage(string $type, array $group)
    {
        $opds = $this->getOpdsInit();
        $opds->title(ucfirst($type).' - '.self::TITLE);
        $feeds = [];
        foreach ($group as $item) {
            $feeds[] = new OpdsEntryNavigation('group:'.$type.':'.$item['item'], $item['item'], $this->router->generate('opds_group_item', ['type' => $type,'accessKey'=>$this->currentAccessKey, 'item' => $item['item']], referenceType: UrlGeneratorInterface::ABSOLUTE_URL));
        }
        $opds->feeds($feeds);

        return $this->convertOpdsResponse($opds->get()->getResponse());
    }

    public function getGroupItemPage(string $type, string $item, array $books)
    {
        $opds = $this->getOpdsInit();
        $opds->title($item.' - '.self::TITLE);
        $feeds = [];
        foreach ($books as $book) {
            $feeds[] = $this->convertBookToOpdsEntry($book);
        }
        $opds->feeds($feeds);

        return $this->convertOpdsResponse($opds->get()->getResponse());
    }

    protected function getOpdsAuthor(string $author)
    {
        return new OpdsEntryBookAuthor($author, $this->router->generate('opds_group_item', ['type' => 'authors','accessKey'=>$this->currentAccessKey, 'item' => $author], referenceType: UrlGeneratorInterface::ABSOLUTE_URL));
    }

    protected function convertBookToOpdsEntry(Book $book): OpdsEntryBook
    {
        $cover = $this->imagineCacheManager->getBrowserPath('covers/'.$book->getImagePath().$book->getImageFilename(), 'thumb');

        return new OpdsEntryBook(
            'book:'.$book->getId(),
            $book->getTitle(),
            $this->router->generate('app_book', ['book' => $book->getId(), 'slug' => $book->getSlug()], referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            content: $book->getSummary() ?? ' ',
            updated: $book->getUpdated(),
            download: $this->router->generate('app_dashboard', referenceType: UrlGeneratorInterface::ABSOLUTE_URL).$this->bookFileSystemManager->getBookPublicPath($book),
            mediaThumbnail: $cover,
            categories: $book->getTags(),
            authors: array_map(fn ($author) => $this->getOpdsAuthor($author), $book->getAuthors()),
            volume: $book->getSerieIndex(),
            serie: $book->getSerie(),
            language: $book->getLanguage() ?? ' ',
            publisher: $book->getPublisher(),
        );
    }
}
