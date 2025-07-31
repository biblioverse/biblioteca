<?php

namespace App\Service\Search;

use App\Repository\BookRepository;

class PublishersDataGenerator extends AbstractBookDataDataGenerator
{
    public function __construct(
        protected BookRepository $bookRepository,
    ) {
    }

    public static function getName(): string
    {
        return 'publisher';
    }

    public function getGroup(): array
    {
        return $this->bookRepository->getAllPublishers();
    }
}
