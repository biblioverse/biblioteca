<?php

namespace App\Mapper;

use App\Entity\Book;
use App\Repository\BookRepository;
use Biblioteca\TypesenseBundle\Mapper\MapperInterface;
use Biblioteca\TypesenseBundle\Mapper\Mapping;

class BookMapper implements MapperInterface
{
    public function __construct(
        private readonly BookRepository $bookRepository,
    ) {
    }

    public function getMapping(): Mapping
    {
        $mapping = new Mapping('books');
        $mapping->setField('id', ['name' => 'id', 'type' => 'primary']);
        $mapping->setField('title', ['name' => 'title', 'type' => 'string']);
        $mapping->setField('sortable_id', ['name' => 'sortable_id', 'type' => 'int32']);
        $mapping->setField('serie', ['name' => 'serie', 'type' => 'string', 'optional' => true, 'facet' => true]);
        $mapping->setField('summary', ['name' => 'summary', 'type' => 'string', 'optional' => true]);
        $mapping->setField('serieIndex', ['name' => 'serieIndex', 'type' => 'string', 'optional' => true]);
        $mapping->setField('extension', ['name' => 'extension', 'type' => 'string', 'facet' => true]);
        $mapping->setField('authors', ['name' => 'authors', 'type' => 'string[]', 'facet' => true]);
        $mapping->setField('tags', ['name' => 'tags', 'type' => 'string[]', 'facet' => true, 'optional' => true]);

        return $mapping;
    }

    public function getData(): \Generator
    {
        $queryBuilder = $this->bookRepository->createQueryBuilder('book')
            ->select('book')
            ->orderBy('book.id', 'ASC');

        $query = $queryBuilder->getQuery();

        foreach ($query->toIterable() as $data) {
            yield $this->transform($data);
        }
    }

    public function getDataCount(): ?int
    {
        $queryBuilder = $this->bookRepository->createQueryBuilder('book')
            ->select('COUNT(distinct book.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function transform(Book $book): array
    {
        return [
            'id' => (string) $book->getId(),
            'title' => $book->getTitle(),
            'sortable_id' => $book->getId(),
            'serie' => (string) $book->getSerie(),
            'summary' => (string) $book->getSummary(),
            'serieIndex' => (string) $book->getSerieIndex(),
            'extension' => $book->getExtension(),
            'authors' => $book->getAuthors(),
            'tags' => $book->getTags(),
        ];
    }
}
