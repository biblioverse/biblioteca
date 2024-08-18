<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\Kepubify\KepubifyConversionFailed;
use App\Kobo\Kepubify\KepubifyEnabler;
use App\Kobo\SyncToken;
use Psr\Log\LoggerInterface;

class MetadataResponseService
{
    public const KEPUB_FORMAT = 'KEPUB';
    public const EPUB3_FORMAT = 'EPUB3';
    public const EPUB_FORMAT = 'EPUB';

    public function __construct(
        protected DownloadHelper $downloadHelper,
        protected KepubifyEnabler $kepubifyEnabler,
        protected LoggerInterface $koboLogger,
    ) {
    }

    protected function getDownloadUrls(Book $book, KoboDevice $kobo, ?array $filters = []): array
    {
        $platforms = $filters['DownloadUrlFilter'] ?? [];
        $platform = reset($platforms);
        $platform = $platform === false ? 'Generic' : $platform;

        // If format conversion is enabled, we convert the book to KEPUB and return it
        if ($this->kepubifyEnabler->isEnabled()) {
            try {
                $downloadInfo = $this->downloadHelper->getDownloadInfo($book, $kobo, self::KEPUB_FORMAT);

                return [0 => [
                    'Format' => self::KEPUB_FORMAT,
                    'Size' => $downloadInfo->getSize(),
                    'Url' => $downloadInfo->getUrl(),
                    'Platform' => $platform,
                ]];
            } catch (KepubifyConversionFailed $e) {
                $this->koboLogger->info('Conversion failed for book {book}', ['book' => $book->getUuid(), 'exception' => $e]);
            }
        }

        // Otherwise, we return the original book with a EPUB3 format
        $downloadInfo = $this->downloadHelper->getDownloadInfo($book, $kobo, $book->getExtension());

        return [0 => [
            'Format' => self::EPUB3_FORMAT,
            'Size' => $downloadInfo->getSize(),
            'Url' => $downloadInfo->getUrl(),
            'Platform' => $platform,
        ]];
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
