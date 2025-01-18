<?php

namespace App\Exception;

class BookExtractionException extends \RuntimeException
{
    public function __construct(string $message, string $bookPath, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf("Failed extraction of '%s' with the error '%s'", $message, $bookPath), 0, $previous);
    }
}
