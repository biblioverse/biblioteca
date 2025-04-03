<?php

namespace App\Service\Search;

use App\Repository\BookRepository;

class AuthorsDataGenerator extends AbstractBookDataDataGenerator
{
    public function __construct(
        protected BookRepository $bookRepository,
    ) {
    }

    public static function getName(): string
    {
        return 'authors';
    }

    public function getGroup(): array
    {
        return $this->bookRepository->getAllAuthors();
    }
}
