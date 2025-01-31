<?php

namespace App\Kobo;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SyncToken
{
    public string $version = '1-1-0';
    public ?\DateTimeImmutable $lastModified = null;
    public ?\DateTimeImmutable $lastCreated = null;
    public ?\DateTimeImmutable $archiveLastModified = null;
    public ?\DateTimeImmutable $readingStateLastModified = null;
    public ?\DateTimeImmutable $tagLastModified = null;
    public ?string $rawKoboStoreToken = null;
    public array $filters = [];

    public int $page = 1;

    public static function fromArray(array $lastSyncToken): SyncToken
    {
        $fromAtom = fn (?string $date): ?\DateTimeImmutable => $date !== null ? new \DateTimeImmutable($date, new \DateTimeZone('UTC')) : null;
        $token = new self();
        $token->version = $lastSyncToken['version'] ?? $token->version;
        $token->lastModified = $fromAtom($lastSyncToken['lastModified'] ?? null);
        $token->lastCreated = $fromAtom($lastSyncToken['lastCreated'] ?? null);
        $token->archiveLastModified = $fromAtom($lastSyncToken['archiveLastModified'] ?? null);
        $token->readingStateLastModified = $fromAtom($lastSyncToken['readingStateLastModified'] ?? null);
        $token->tagLastModified = $fromAtom($lastSyncToken['tagLastModified'] ?? null);
        $token->rawKoboStoreToken = $lastSyncToken['rawKoboStoreToken'] ?? $token->rawKoboStoreToken;
        $token->filters = $lastSyncToken['filters'] ?? $token->filters;
        $token->page = $lastSyncToken['page'] ?? 1;

        return $token;
    }

    public function getFilterResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'Filter' => 'ALL',
            'DownloadUrlFilter' => 'Generic,Android',
            'PrioritizeRecentReads' => true,
        ]);
        $resolver->setAllowedTypes('Filter', 'string');
        $resolver->setAllowedTypes('DownloadUrlFilter', ['string', 'array', 'null']);
        $resolver->setAllowedValues('DownloadUrlFilter', ['Generic', 'Android', 'Generic,Android']);
        $resolver->setAllowedTypes('PrioritizeRecentReads', ['string', 'bool']);

        $resolver->setNormalizer('DownloadUrlFilter', function (Options $options, string|array|null $value) {
            $result = is_array($value) ? $value : explode(',', (string) $value);
            if ($result === []) {
                return null;
            }
        });
        $resolver->setNormalizer('PrioritizeRecentReads', fn (Options $options, string|bool $value) => in_array(strtolower((string) $value), ['true', '1', 'yes'], true));

        return $resolver;
    }

    public function maxLastModified(?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $this->lastModified,
            ...$value
        );
    }

    public function maxLastCreated(?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $this->lastCreated,
            ...$value
        );
    }

    protected static function max(?\DateTimeImmutable ...$dates): ?\DateTimeImmutable
    {
        $max = null;
        foreach ($dates as $date) {
            if (!$date instanceof \DateTimeImmutable) {
                continue;
            }

            if (!$max instanceof \DateTimeImmutable || $date > $max) {
                $max = $date;
            }
        }

        return $max;
    }

    public function toArray(): array
    {
        $format = \DateTime::W3C;

        // Sort alphabetically
        return [
            'archiveLastModified' => $this->archiveLastModified?->format($format),
            'filters' => $this->filters,
            'lastCreated' => $this->lastCreated?->format($format),
            'lastModified' => $this->lastModified?->format($format),
            'page' => $this->page,
            'rawKoboStoreToken' => $this->rawKoboStoreToken,
            'readingStateLastModified' => $this->readingStateLastModified?->format($format),
            'tagLastModified' => $this->tagLastModified?->format($format),
            'version' => $this->version,
        ];
    }

    public function markLastSyncDateAndResetPage(): self
    {
        $now = new \DateTimeImmutable('now');
        $this->lastModified = $now;
        $this->tagLastModified = $now;
        $this->readingStateLastModified = $now;
        $this->lastCreated = $now;
        $this->archiveLastModified = $now;
        $this->page = 1;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function setTagLastModified(?\DateTimeImmutable $lastModified): self
    {
        $this->tagLastModified = $lastModified;

        return $this;
    }
}
