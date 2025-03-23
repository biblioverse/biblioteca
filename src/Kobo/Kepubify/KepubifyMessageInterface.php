<?php

namespace App\Kobo\Kepubify;

use App\Entity\Book;

/**
 * Convert an ebook with kepubify binary
 * The destination property will be set to the path of the converted file,
 */
interface KepubifyMessageInterface
{
    public function getDestination(): ?string;

    public function setDestination(?string $destination): void;

    public function getSize(): ?int;

    public function setSize(?int $size): void;

    public function getBook(): Book;
}
