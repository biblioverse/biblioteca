<?php

namespace App\Service;

use App\Entity\Book;
use Google\Client;
use Google\Service\Books;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\ItemInterface;

class BookSuggestions
{
    public const EMPTY_SUGGESTIONS = [
        'image' => [],
        'title' => [],
        'authors' => [],
        'publisher' => [],
        'tags' => [],
        'summary' => [],
    ];

    public function __construct(private ParameterBagInterface $parameterBag, private SluggerInterface $slugger)
    {
    }

    public function getOpenlibraryQueryFromBook(Book $book, int $level = 1): array
    {
        $mainAuthor = current($book->getAuthors());

        return match ($level) {
            1 => ['q' => 'title:'.$book->getTitle().' author:'.$mainAuthor, 'fields' => 'title,author_name,key,cover_i,subject'],
            2 => ['q' => 'title:'.$book->getSerie().' '.$book->getSerieIndex().' author:'.$mainAuthor, 'fields' => 'title,author_name,key,cover_i,subject'],
            default => throw new \RuntimeException('Invalid level')
        };
    }

    public function getOpenLibrarySuggestions(Book $book): array
    {
        $cache = new FilesystemAdapter();

        $cacheKey = $this->slugger->slug('oopenlib-'.$book->getId());

        return $cache->get($cacheKey, function (ItemInterface $item) use ($book): array {
            $item->expiresAfter(3600);

            $client = new \GuzzleHttp\Client();

            $results = null;
            for ($i = 1; $i <= 2; $i++) {
                $query = $this->getOpenlibraryQueryFromBook($book, $i);
                $results = $client->request('GET', 'https://openlibrary.org/search.json', ['query' => $query])->getBody()->getContents();
                $results = json_decode($results, true);
                if (is_array($results) && array_key_exists('docs', $results) && count($results['docs']) > 0) {
                    break;
                }
            }
            $suggestions = self::EMPTY_SUGGESTIONS;
            if (!is_array($results) || !array_key_exists('docs', $results)) {
                return $suggestions;
            }

            foreach ($results['docs'] as $result) {
                if (array_key_exists('subject', $result) && is_array($result['subject'])) {
                    foreach ($result['subject'] as $category) {
                        $suggestions['tags'][$category] = $category;
                    }
                }
            }

            return $suggestions;
        });
    }

    public function getGoogleQueryFromBook(Book $book, int $level = 1): string
    {
        return match ($level) {
            1 => 'intitle:"'.$book->getSerie().' '.$book->getSerieIndex().' '.$book->getTitle().'" inauthor:'.implode(' ', $book->getAuthors()),
            2 => 'intitle:"'.$book->getSerie().' '.$book->getSerieIndex().'" inauthor:'.implode(' ', $book->getAuthors()),
            3 => 'intitle:"'.$book->getTitle().'" inauthor:'.implode(' ', $book->getAuthors()),
            4 => 'intitle:"'.$book->getTitle().'"',
            default => throw new \RuntimeException('Invalid level')
        };
    }

    /**
     * @return array<string[]>
     */
    public function getGoogleSuggestions(Book $book): array
    {
        $key = $this->parameterBag->get('GOOGLE_API_KEY');

        $suggestions = self::EMPTY_SUGGESTIONS;

        if ('' === $key || !is_string($key)) {
            return $suggestions;
        }

        $cache = new FilesystemAdapter();

        $cacheKey = $this->slugger->slug('google-'.$book->getId());

        return $cache->get($cacheKey, function (ItemInterface $item) use ($key, $book, $suggestions): array {
            $item->expiresAfter(3600);

            $client = new Client();

            $client->setApplicationName('biblioteca');
            $client->setDeveloperKey($key);

            $service = new Books($client);
            $optParams = [];

            $results = null;
            for ($i = 1; $i <= 4; $i++) {
                $query = $this->getGoogleQueryFromBook($book, $i);
                $results = $service->volumes->listVolumes($query, $optParams);
                if ($results->getTotalItems() > 0) {
                    break;
                }
            }

            if (0 === $results->getTotalItems()) {
                return $suggestions;
            }

            foreach ($results->getItems() as $result) {
                $volumeInfo = $result->getVolumeInfo();

                if (null !== $volumeInfo->getImageLinks()) {
                    $imageLinks = $volumeInfo->getImageLinks();
                    $suggestions['image'][] = $imageLinks->getExtraLarge() ??
                        $imageLinks->getLarge() ??
                        $imageLinks->getMedium() ??
                        $imageLinks->getThumbnail() ??
                        $imageLinks->getSmallThumbnail();
                }
                if (null !== $volumeInfo->getPublisher()) {
                    $suggestions['publisher'][$volumeInfo->getPublisher()] = $volumeInfo->getPublisher();
                }
                if (null !== $volumeInfo->getTitle()) {
                    $suggestions['title'][$volumeInfo->getTitle()] = $volumeInfo->getTitle();
                }
                if (null !== $volumeInfo->getDescription()) {
                    $suggestions['summary'][md5($volumeInfo->getDescription())] = $volumeInfo->getDescription();
                }
                if (null !== $volumeInfo->getAuthors()) {
                    foreach ($volumeInfo->getAuthors() as $author) {
                        $suggestions['authors'][$author] = $author;
                    }
                }if (null !== $volumeInfo->getCategories()) {
                    foreach ($volumeInfo->getCategories() as $category) {
                        $suggestions['tags'][$category] = $category;
                    }
                }
            }

            return $suggestions;
        });
    }
}
