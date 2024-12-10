<?php

namespace App\Kobo;

class BookDownloadInfo
{
    public function __construct(private readonly int $size, private readonly string $url)
    {
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
