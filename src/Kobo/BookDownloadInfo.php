<?php

namespace App\Kobo;

class BookDownloadInfo
{
    public function __construct(private int $size, private string $url)
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
