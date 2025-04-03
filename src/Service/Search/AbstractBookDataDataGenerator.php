<?php

namespace App\Service\Search;

use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\Options\CollectionOptions;
use Biblioverse\TypesenseBundle\Mapper\StandaloneCollectionManagerInterface;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;

abstract class AbstractBookDataDataGenerator implements DataGeneratorInterface, StandaloneCollectionManagerInterface
{
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
                name: 'name',
                type: DataTypeEnum::STRING
            )
            ->add(
                name: 'sortable_id',
                type: DataTypeEnum::INT32
            )
            ->add(
                name: 'first_letter',
                type: DataTypeEnum::STRING,
                facet: true,
                optional: false,
            )
            ->add(
                name: 'book_count',
                type: DataTypeEnum::INT32,
                optional: true
            );
    }

    public function getData(): \Generator
    {
        $groupData = $this->getGroup();

        foreach ($groupData as $data) {
            if ($data['item'] === null || $data['item'] === '') {
                continue;
            }

            $firstLetter = strtoupper(substr((string) $data['item'], 0, 1));

            if (!$this->isFirstCharLetter($firstLetter)) {
                $firstLetter = '#';
            }

            yield [
                'id' => (string) crc32((string) $data['item']),
                'name' => $data['item'],
                'sortable_id' => crc32((string) $data['item']),
                'first_letter' => $firstLetter,
                'book_count' => $data['bookCount'],
            ];
        }
    }

    public function getDataCount(): ?int
    {
        return count($this->getGroup());
    }

    protected function isFirstCharLetter(string $string): bool
    {
        return ctype_alpha($string[0]);
    }

    abstract public function getGroup(): array;

    abstract public static function getName(): string;
}
