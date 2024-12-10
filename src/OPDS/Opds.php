<?php

namespace App\OPDS;

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
    protected string $currentAccessKey;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly CacheManager $imagineCacheManager,
        private readonly BookFileSystemManager $bookFileSystemManager,
    ) {
    }

    public function setAccessKey(string $accessKey): void
    {
        $this->currentAccessKey = $accessKey;
    }

    public function getOpdsConfig(): KiwilanOpds
    {
        return KiwilanOpds::make(new OpdsConfig(
            name: 'Biblioteca',
            author: 'Biblioteca',
            authorUrl: $this->router->generate('app_dashboard', [], referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            startUrl: $this->router->generate('opds_start', ['accessKey' => $this->currentAccessKey], referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            searchUrl: $this->router->generate('opds_search', ['accessKey' => $this->currentAccessKey], referenceType: UrlGeneratorInterface::ABSOLUTE_URL), // Search URL, will be included in top navigation
            updated: new \DateTime(), // Last update of OPDS feed
            maxItemsPerPage: 32, // Max items per page, default is 16
        ))
        ->title('Biblioteca');
    }

    public function convertOpdsResponse(?OpdsResponse $response): Response
    {
        if (!$response instanceof OpdsResponse) {
            return new Response('No response', Response::HTTP_NO_CONTENT);
        }
        $symfonyResponse = new Response();
        $symfonyResponse->setContent($response->getContents());
        foreach ($response->getHeaders() as $key => $value) {
            $symfonyResponse->headers->set($key, $value);
        }

        return $symfonyResponse;
    }

    public function getOpdsAuthor(string $author): OpdsEntryBookAuthor
    {
        return new OpdsEntryBookAuthor($author, $this->router->generate('opds_group_item', ['type' => 'authors', 'accessKey' => $this->currentAccessKey, 'item' => $author], referenceType: UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function getNavigationEntry(string $id, string $title, string $url): OpdsEntryNavigation
    {
        return new OpdsEntryNavigation($id, $title, $url);
    }

    public function convertBookToOpdsEntry(Book $book): OpdsEntryBook
    {
        $cover = $this->imagineCacheManager->getBrowserPath('covers/'.$book->getImagePath().$book->getImageFilename(), 'thumb');

        $updated = $book->getUpdated();
        if (!$updated instanceof \DateTimeInterface) {
            $updated = new \DateTime();
        }

        return new OpdsEntryBook(
            'book:'.$book->getId(),
            $book->getTitle(),
            $this->router->generate('app_book', ['book' => $book->getId(), 'slug' => $book->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
            content: $book->getSummary() ?? ' ',
            updated: $updated->format('Y-m-d H:i:s'),
            download: $this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL).$this->bookFileSystemManager->getBookPublicPath($book),
            mediaThumbnail: $cover,
            categories: $book->getTags() ?? [],
            authors: array_map(fn ($author) => $this->getOpdsAuthor($author), $book->getAuthors()),
            volume: $book->getSerieIndex(),
            serie: $book->getSerie(),
            language: $book->getLanguage() ?? ' ',
            publisher: $book->getPublisher(),
        );
    }
}
