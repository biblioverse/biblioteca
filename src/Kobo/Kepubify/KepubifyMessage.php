<?php

namespace App\Kobo\Kepubify;

use App\Entity\Book;

/**
 * Convert an ebook with kepubify binary
 * The destination property will be set to the path of the converted file,
 */
class KepubifyMessage implements KepubifyMessageInterface
{
    /**
     * @var string|null Path of the destination file (which aim to be a temporary file). Null if the conversion failed.
     */
    private ?string $destination = null;
    /**
     * @var int|null File size of the destination file
     */
    private ?int $size = null;

    public function __construct(
        /** Path of the source ebook to convert */
        public Book $book,
    ) {
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getBook(): Book
    {
        return $this->book;
    }
}
