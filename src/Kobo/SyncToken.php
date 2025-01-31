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

    public ?\DateTimeImmutable $currentDate = null;

    public static function fromArray(array $lastSyncToken): SyncToken
    {
        $fromAtom = fn (?string $date): ?\DateTimeInterface => $date !== null ? new \DateTimeImmutable($date) : null;
        $token = new self();
        $token->version = $lastSyncToken['version'] ?? $token->version;
        $token->lastModified = $fromAtom($lastSyncToken['lastModified'] ?? null);
        $token->lastCreated = $fromAtom($lastSyncToken['lastCreated'] ?? null);
        $token->archiveLastModified = $fromAtom($lastSyncToken['archiveLastModified'] ?? null);
        $token->readingStateLastModified = $fromAtom($lastSyncToken['readingStateLastModified'] ?? null);
        $token->tagLastModified = $fromAtom($lastSyncToken['tagLastModified'] ?? null);
        $token->rawKoboStoreToken = $lastSyncToken['rawKoboStoreToken'] ?? $token->rawKoboStoreToken;
        $token->filters = $lastSyncToken['filters'] ?? $token->filters;
        $token->currentDate = $fromAtom($lastSyncToken['currentDate'] ?? null);

        return $token;
    }

    public static function createDummy(): SyncToken
    {
        $token = new self();
        $token->currentDate = new \DateTimeImmutable('now');

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
        return [
            'version' => $this->version,
            'currentDate' => $this->currentDate?->format(\DateTime::ATOM),
            'lastModified' => $this->lastModified?->format(\DateTime::ATOM),
            'lastCreated' => $this->lastCreated?->format(\DateTime::ATOM),
            'archiveLastModified' => $this->archiveLastModified?->format(\DateTime::ATOM),
            'readingStateLastModified' => $this->readingStateLastModified?->format(\DateTime::ATOM),
            'tagLastModified' => $this->tagLastModified?->format(\DateTime::ATOM),
            'rawKoboStoreToken' => $this->rawKoboStoreToken,
            'filters' => $this->filters,
        ];
    }

    public function marksAsLastUsed(): self
    {
        if (!$this->currentDate instanceof \DateTimeInterface) {
            $this->currentDate = new \DateTimeImmutable('now');
        }
        $this->lastModified = $this->tagLastModified = $this->readingStateLastModified = $this->lastCreated = $this->archiveLastModified = $this->currentDate;

        return $this;
    }
}
