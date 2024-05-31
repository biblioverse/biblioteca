<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\SyncToken;

class MetadataResponseService
{
    public function __construct(protected DownloadHelper $downloadHelper)
    {
    }

    protected function getDownloadUrls(Book $book, KoboDevice $kobo, ?array $filters = []): array
    {
        $platforms = $filters['DownloadUrlFilter'] ?? [];
        $platform = reset($platforms);
        $platform = $platform === false ? 'Generic' : $platform;

        $response = [];

        // $formats = $this->downloadHelper->isEpub3($book) ? ['EPUB3'] : ['EPUB'];
        $formats = ['EPUB3', 'EPUB']; // EPUB3 is required for Kobo
        foreach ($formats as $format) { // and ... EPUB3FL ?;
            $response[] = [
                'Format' => $format,
                'Size' => $this->downloadHelper->getSize($book),
                'Url' => $this->downloadHelper->getUrlForKoboDevice($book, $kobo),
                'Platform' => $platform,
            ];
        }

        return $response;
    }

    public function fromBook(Book $book, KoboDevice $kobo, ?SyncToken $syncToken = null): array
    {
        $data = [
            'Categories' => ['00000000-0000-0000-0000-000000000001'],
            'CoverImageId' => $book->getUuid(),
            'CrossRevisionId' => $book->getUuid(),
            'CurrentDisplayPrice' => ['CurrencyCode' => 'USD', 'TotalAmount' => 0],
            'CurrentLoveDisplayPrice' => ['TotalAmount' => 0],
            'Description' => $book->getSummary(),
            'DownloadUrls' => $this->getDownloadUrls($book, $kobo, $syncToken?->filters),
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
            'RevisionId' => $book->getUuid(),
            'Title' => $book->getTitle(),
            'WorkId' => $book->getUuid(),
            'ContributorRoles' => array_map(fn (string $author) => ['author' => $author], $book->getAuthors()),
            'Contributor' => $book->getAuthors(),
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
