<?php

namespace App\Kobo\SyncToken;

abstract class AbstractSyncToken implements SyncTokenInterface
{
    protected array $filters = [];

    protected int $page = 1;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
