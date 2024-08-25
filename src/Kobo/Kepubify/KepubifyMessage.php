<?php

namespace App\Kobo\Kepubify;

/**
 * Convert an ebook with kepubify binary
 * The destination property will be set to the path of the converted file,
 */
class KepubifyMessage
{
    /**
     * @var string|null Path of the destination file (which aim to be a temporary file). Null if the conversion failed.
     */
    public ?string $destination = null;

    public function __construct(
        /** Path of the source ebook to convert */
        public string $source
    ) {
    }
}
