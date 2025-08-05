<?php

namespace App\Service\Search;

use App\Repository\BookRepository;

class SeriesDataGenerator extends AbstractBookDataDataGenerator
{
    public function __construct(
        protected BookRepository $bookRepository,
    ) {
    }

    public static function getName(): string
    {
        return 'serie';
    }

    public function getGroup(): array
    {
        return $this->bookRepository->getAllSeries();
    }
}
