<?php

namespace App\Twig;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class Search
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    public function __construct(private BookRepository $bookRepository)
    {
    }

    /**
     * @return array<Book>
     */
    public function getBooks(): array
    {
        if (null === $this->query) {
            return [];
        }

        return $this->bookRepository->search($this->query);
    }
}
