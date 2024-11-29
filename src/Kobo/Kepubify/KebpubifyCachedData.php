<?php

namespace App\Kobo\Kepubify;

class KebpubifyCachedData implements \JsonSerializable
{
    private string $content;
    private int $size;

    public function __construct(string $filename)
    {
        $this->size = (int) filesize($filename);
        $this->content = (string) file_get_contents($filename);
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function jsonSerialize(): array
    {
        return [
            'size' => $this->size,
            'content' => $this->content,
        ];
    }
}