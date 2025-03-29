<?php

namespace App\Service\Search;

use App\Repository\BookRepository;

class TagsDataGenerator extends AbstractBookDataDataGenerator
{
    public function __construct(
        protected BookRepository $bookRepository,
    ) {
    }

    public static function getName(): string
    {
        return 'tags';
    }

    public function getGroup(): array
    {
        return $this->bookRepository->getAllTags();
    }
}
