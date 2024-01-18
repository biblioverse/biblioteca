<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookFileNotFound extends NotFoundHttpException
{
    public function __construct(string $bookPath)
    {
        parent::__construct(sprintf('Book file %s not found', $bookPath));
    }
}
