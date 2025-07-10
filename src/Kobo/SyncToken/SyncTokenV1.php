<?php

namespace App\Kobo\SyncToken;

/**
 * This is the default token class used internally for synchronizing book based on dates.
 */
class SyncTokenV1 extends AbstractSyncToken implements \Stringable
{
    public const string VERSION = '1-1-0';

    public string $version = self::VERSION;
    public ?\DateTimeImmutable $lastModified = null;
    public ?\DateTimeImmutable $lastCreated = null;
    public ?\DateTimeImmutable $archiveLastModified = null;
    public ?\DateTimeImmutable $readingStateLastModified = null;
    public ?\DateTimeImmutable $tagLastModified = null;
    public ?\DateTimeImmutable $deletedTagLastModified = null;
    public ?string $rawKoboStoreToken = null;

    public ?bool $isContinuation = null;

    public static function fromArray(array $lastSyncToken): SyncTokenV1
    {
        return self::copy($lastSyncToken, new self());
    }

    private static function copy(array $lastSyncToken, SyncTokenV1 $destination): SyncTokenV1
    {
        $fromAtom = fn (?string $date): ?\DateTimeImmutable => $date !== null ? new \DateTimeImmutable($date) : null;
        $destination->version = $lastSyncToken['version'] ?? $destination->version;
        $destination->lastModified = $fromAtom($lastSyncToken['lastModified'] ?? null);
        $destination->lastCreated = $fromAtom($lastSyncToken['lastCreated'] ?? null);
        $destination->archiveLastModified = $fromAtom($lastSyncToken['archiveLastModified'] ?? null);
        $destination->readingStateLastModified = $fromAtom($lastSyncToken['readingStateLastModified'] ?? null);
        $destination->tagLastModified = $fromAtom($lastSyncToken['tagLastModified'] ?? null);
        $destination->deletedTagLastModified = $fromAtom($lastSyncToken['deletedTagLastModified'] ?? null);
        $destination->rawKoboStoreToken = $lastSyncToken['rawKoboStoreToken'] ?? $destination->rawKoboStoreToken;
        $destination->filters = $lastSyncToken['filters'] ?? $destination->filters;
        $destination->page = $lastSyncToken['page'] ?? 1;

        return $destination;
    }

    public function withPage(int $page): self
    {
        $result = self::copy($this->toArray(), $this);
        $result->setPage($page);

        return $result;
    }

    public function toArray(): array
    {
        $format = \DateTime::W3C;

        // Sort alphabetically
        $data = [
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

        return $data;
    }

    #[\Override]
    public function setPage(int $page): self
    {
        parent::setPage($page);
        if ($this->page > 1) {
            $this->isContinuation = true;
        }

        return $this;
    }

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function markLastSyncDateAndResetPage(): self
    {
        $now = new \DateTimeImmutable('now');
        $this->lastModified = $now;
        $this->tagLastModified = $now;
        $this->readingStateLastModified = $now;
        $this->lastCreated = $now;
        $this->archiveLastModified = $now;
        $this->deletedTagLastModified = $now;
        $this->page = 1;

        return $this;
    }

    public function setTagLastModified(?\DateTimeImmutable $lastModified): self
    {
        $this->tagLastModified = $lastModified;

        return $this;
    }

    public function getLastCreated(): ?\DateTimeImmutable
    {
        return $this->lastCreated;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function getArchiveLastModified(): ?\DateTimeImmutable
    {
        return $this->archiveLastModified;
    }

    public function getReadingStateLastModified(): ?\DateTimeImmutable
    {
        return $this->readingStateLastModified;
    }

    public function getTagLastModified(): ?\DateTimeImmutable
    {
        return $this->tagLastModified;
    }

    public function isContinuation(): ?bool
    {
        return $this->isContinuation;
    }
}
