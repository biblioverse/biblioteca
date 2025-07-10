<?php

namespace App\Kobo\SyncToken;

/**
 * Token representing the KoboDevice's last sync timestamp.
 * Used to:
 *  - Determine when the last synchronization occurred.
 *  - Fetch only data updated since that timestamp.
 *  - Decide whether objects are created, updated, or deleted in the response.
 *
 * Example: a book is placed in ChangedEntitlement or NewEntitlement
 * depending on its creation date relative to the token.
 */
interface SyncTokenInterface
{
    public function getLastCreated(): ?\DateTimeImmutable;

    public function getLastModified(): ?\DateTimeImmutable;

    public function getArchiveLastModified(): ?\DateTimeImmutable;

    public function getReadingStateLastModified(): ?\DateTimeImmutable;

    public function getTagLastModified(): ?\DateTimeImmutable;

    public function getFilters(): ?array;

    public function setFilters(array $filters): self;

    public function toArray(): array;

    public function setPage(int $page): self;

    public function getPage(): int;

    public function withPage(int $page): SyncTokenInterface;

    public function markLastSyncDateAndResetPage(): self;

    public function setTagLastModified(?\DateTimeImmutable $lastModified): self;
}
