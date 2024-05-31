<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookFileNotFound extends NotFoundHttpException
{
    public function __construct(?string $bookPath)
    {
        if ($bookPath === null) {
            parent::__construct('Book file not found');

            return;
        }
        parent::__construct(sprintf('Book file %s not found', $bookPath));
    }
}
