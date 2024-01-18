<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\Kobo;
use App\Kobo\DownloadHelper;
use App\Kobo\SyncToken;

class MetadataResponseService
{
    public function __construct(protected DownloadHelper $downloadHelper)
    {
    }

    protected function getDownloadUrls(Book $book, Kobo $kobo, array $filters = []): array
    {
        $platforms = $filters['DownloadUrlFilter'] ?? [];
        $platform = reset($platforms);
        $platform = $platform === false ? 'Generic' : $platform;

        return [
            'Format' => 'EPUB3FL',
            'Size' => $this->downloadHelper->getSize($book),
            'Url' => $this->downloadHelper->getUrlForKobo($book, $kobo),
            'Platform' => $platform,
        ];
    }

    public function fromBook(Book $book, Kobo $kobo, SyncToken $syncToken): array
    {
        $data = [
            'Categories' => ['00000000-0000-0000-0000-000000000001'],
            'CoverImageId' => $book->getUuid(),
            'CrossRevisionId' => $book->getUuid(),
            'CurrentDisplayPrice' => ['CurrencyCode' => 'USD', 'TotalAmount' => 0],
            'CurrentLoveDisplayPrice' => ['TotalAmount' => 0],
            'Description' => $book->getSummary(),
            'DownloadUrls' => $this->getDownloadUrls($book, $kobo, $syncToken->filters),
            'EntitlementId' => $book->getUuid(),
            'ExternalIds' => [],
            'Genre' => '00000000-0000-0000-0000-000000000001',
            'IsEligibleForKoboLove' => false,
            'IsInternetArchive' => false,
            'IsPreOrder' => false,
            'IsSocialEnabled' => true,
            'Language' => $book->getLanguage(),
            'PhoneticPronunciations' => [],
            'PublicationDate' => $book->getPublishDate(),
            'Publisher' => [
                'Imprint' => '',
                'Name' => $book->getPublisher() ?? 'Unknown'],
            'RevisionId' => 0,
            'Title' => $book->getTitle(),
            'WorkId' => $book->getUuid(),
        ];

        if ($book->getSerie() === null || $book->getSerieIndex() === null) {
            return $data;
        }

        // Add Serie information
        $data['Series'] = [
            'Name' => $book->getSerieIndex(),
            'Number' => (int) $book->getSerieIndex(),
            'NumberFloat' => $book->getSerieIndex(),
            'Id' => md5($book->getSerie()), //  Get a deterministic id based on the series name.
        ];

        return $data;
    }
}
