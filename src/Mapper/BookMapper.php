<?php

namespace App\Mapper;

use App\Entity\Book;
use Biblioteca\TypesenseBundle\Mapper\Entity\AbstractEntityMapper;
use Biblioteca\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioteca\TypesenseBundle\Mapper\Options\CollectionOptions;
use Biblioteca\TypesenseBundle\Mapper\StandaloneMapperInterface;
use Biblioteca\TypesenseBundle\Type\DataTypeEnum;

/**
 * @extends AbstractEntityMapper<Book>
 */
class BookMapper extends AbstractEntityMapper implements StandaloneMapperInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'books';
    }

    #[\Override]
    public function getMapping(): Mapping
    {
        $mapping = new Mapping(collectionOptions: new CollectionOptions(
            tokenSeparators: [' ', '-', "'"],
            symbolsToIndex: ['+', '#', '@', '_'],
            defaultSortingField: 'sortable_id'
        ));

        return $mapping->
            add(
                name: 'id',
                type: DataTypeEnum::STRING
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
    }

    #[\Override]
    public function transform(object $data): array
    {
        return [
            'id' => (string) $data->getId(),
            'title' => $data->getTitle(),
            'sortable_id' => $data->getId(),
            'serie' => (string) $data->getSerie(),
            'summary' => (string) $data->getSummary(),
            'serieIndex' => (string) $data->getSerieIndex(),
            'extension' => $data->getExtension(),
            'authors' => $data->getAuthors(),
            'tags' => $data->getTags(),
        ];
    }

    #[\Override]
    public function getClassName(): string
    {
        return Book::class;
    }
}
