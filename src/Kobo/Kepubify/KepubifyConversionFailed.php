<?php

namespace App\Kobo\Kepubify;

class KepubifyConversionFailed extends \RuntimeException
{
    public function __construct(string $originalPath, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Conversion failed for book %s', $originalPath), 0, $previous);
    }
}
