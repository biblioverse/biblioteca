<?php

namespace App\Mapper;

use App\Entity\Book;
use App\Repository\BookRepository;
use Biblioteca\TypesenseBundle\Mapper\FieldMapping;
use Biblioteca\TypesenseBundle\Mapper\MapperInterface;
use Biblioteca\TypesenseBundle\Mapper\Mapping;
use Biblioteca\TypesenseBundle\Type\DataTypeEnum;

class BookMapper implements MapperInterface
{
    public function __construct(
        private readonly BookRepository $bookRepository,
    ) {
    }

    public function getMapping(): Mapping
    {
        $mapping = new Mapping('books');
        $mapping->
            add(
                name: 'id',
                type: DataTypeEnum::PRIMARY
            )
            ->add(
                name: 'title',
                type: DataTypeEnum::STRING
            )
            ->add(
                name: 'sortable_id',
                type: DataTypeEnum::INT32
            )
            ->add(
                name: 'serie',
                type: DataTypeEnum::STRING,
                facet: true,
                optional: true
            )
            ->add(
                name: 'summary',
                type: DataTypeEnum::STRING,
                optional: true
            )
            ->add(
                name: 'serieIndex',
                type: DataTypeEnum::STRING,
                optional: true
            )
            ->add(
                name: 'extension',
                type: DataTypeEnum::STRING,
                facet: true
            )
            ->add(
                name: 'authors',
                type: DataTypeEnum::STRING_ARRAY,
                facet: true
            )
            ->add(
                name: 'tags',
                type: DataTypeEnum::STRING_ARRAY,
                facet: true,
                optional: true
            )
        ;

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
