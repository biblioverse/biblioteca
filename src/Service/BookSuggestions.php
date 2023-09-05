<?php

namespace App\Service;

use App\Entity\Book;
use Google\Client;
use Google\Service\Books;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BookSuggestions
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    /**
     * @return array<string[]>
     */
    public function getSuggestions(Book $book): array
    {
        $key = $this->parameterBag->get('GOOGLE_API_KEY');

        $suggestions = [
            'image' => [],
            'title' => [],
            'mainAuthor' => [],
            'publisher' => [],
            'tags' => [],
            'summary' => [],
        ];

        if ('' === $key || !is_string($key)) {
            return $suggestions;
        }

        $client = new Client();
        $client->setApplicationName('biblioteca');
        $client->setDeveloperKey($key);

        $service = new Books($client);
        $query = 'intitle:"'.$book->getTitle().'" inauthor:'.$book->getMainAuthor().'';
        $optParams = [
        ];

        $results = $service->volumes->listVolumes($query, $optParams);

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
                    $suggestions['mainAuthor'][$author] = $author;
                }
            }if (null !== $volumeInfo->getCategories()) {
                foreach ($volumeInfo->getCategories() as $category) {
                    $suggestions['tags'][$category] = $category;
                }
            }
        }

        return $suggestions;
    }
}
