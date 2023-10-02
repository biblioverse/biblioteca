<?php

namespace App\Twig;

use App\Entity\Book;
use App\Service\BookSearch;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class Search
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    public function __construct(private BookSearch $bookSearch)
    {
    }

    /**
     * @return array<Book>
     */
    public function getBooks(): array
    {
        if (null === $this->query || '' === $this->query) {
            return [];
        }

        return $this->bookSearch->autocomplete($this->query);
    }
}
