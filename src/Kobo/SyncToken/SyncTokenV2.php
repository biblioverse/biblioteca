<?php

namespace App\Kobo\SyncToken;

/**
 * This token is used upstream by the store.
 */
class SyncTokenV2 extends AbstractSyncToken
{
    public const string VERSION = '2-0-0';
    public const string HEADER = '{"typ":1,"ver":"v2","ptyp":"SyncToken"}';
    public const string HEADER_NO_VERSION = '{"typ":1,"ver":null,"ptyp":"SyncToken"}';

    public function __construct(private array $data, private readonly ?bool $isContinuation = null)
    {
    }

    public function getLastCreated(): ?\DateTimeImmutable
    {
        return isset($this->data['Entitlements']['Timestamp'])
            ? new \DateTimeImmutable($this->data['Entitlements']['Timestamp'])
            : null;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        return isset($this->data['ProductMetadata']['Timestamp'])
            ? new \DateTimeImmutable($this->data['ProductMetadata']['Timestamp'])
            : null;
    }

    public function getArchiveLastModified(): ?\DateTimeImmutable
    {
        return isset($this->data['DeletedEntitlements']['Timestamp'])
            ? new \DateTimeImmutable($this->data['DeletedEntitlements']['Timestamp'])
            : null;
    }

    public function getReadingStateLastModified(): ?\DateTimeImmutable
    {
        return isset($this->data['ReadingStates']['Timestamp'])
            ? new \DateTimeImmutable($this->data['ReadingStates']['Timestamp'])
            : null;
    }

    public function getTagLastModified(): ?\DateTimeImmutable
    {
        return isset($this->data['Tags']['Timestamp'])
            ? new \DateTimeImmutable($this->data['Tags']['Timestamp'])
            : null;
    }

    public function toArray(): array
    {
        return ['data' => $this->data, 'isContinuation' => $this->isContinuation, 'filters' => $this->filters];
    }

    public static function fromArray(array $lastSyncToken): SyncTokenInterface
    {
        return (new self($lastSyncToken['data'], $lastSyncToken['isContinuation'] ?? null))->setFilters($lastSyncToken['filters'] ?? null);
    }

    public function isContinuation(): bool
    {
        return $this->isContinuation === true;
    }

    public function getDeletedTagLastModified(): ?\DateTimeImmutable
    {
        return isset($this->data['DeletedTags']['Timestamp'])
            ? new \DateTimeImmutable($this->data['DeletedTags']['Timestamp'])
            : null;
    }

    public function markLastSyncDateAndResetPage(): self
    {
        $now = new \DateTimeImmutable('now');
        $now = $now->format(\DateTimeInterface::ATOM);
        $this->data['DeletedEntitlements']['Timestamp'] = $now;
        $this->data['DeletedTags']['Timestamp'] = $now;
        $this->data['ProductMetadata']['Timestamp'] = $now;
        $this->data['ReadingStates']['Timestamp'] = $now;
        $this->data['Tags']['Timestamp'] = $now;
        $this->page = 1;

        return $this;
    }

    public function setTagLastModified(?\DateTimeImmutable $lastModified): self
    {
        $this->data['Tags']['Timestamp'] = $lastModified?->format(\DateTimeInterface::ATOM);

        return $this;
    }

    public function withPage(int $page): SyncTokenV2
    {
        $result = new self($this->data, $this->isContinuation);
        $result->setFilters($this->filters);
        $result->setPage($page);

        return $result;
    }
}
