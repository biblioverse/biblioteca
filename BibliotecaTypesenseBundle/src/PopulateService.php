<?php

namespace Biblioteca\TypesenseBundle;

use Biblioteca\TypesenseBundle\Client\ClientInterface;
use Biblioteca\TypesenseBundle\Mapper\MapperInterface;
use Typesense\Collection;

class PopulateService
{
    public function __construct(
        private ClientInterface $client,
        private readonly string $collectionPrefix = '',
    ) {
    }

    public function deleteCollection(MapperInterface $mapper): void
    {
        $list = $this->client->getCollections()->retrieve();
        $names = array_map(fn ($collection) => $collection['name'], $list);
        $name = $this->getMappingName($mapper->getMapping());
        if (in_array($name, $names)) {
            $this->client->getCollections()->__get($name)->delete();
        }
    }

    public function createCollection(MapperInterface $mapper): Collection
    {
        $mapping = $mapper->getMapping();
        $name = $this->getMappingName($mapping);

        $payload = [
            'name' => $name,
            'fields' => array_values($mapping->getFields()),
            ...$mapping->getCollectionOptions(),
        ];

        $this->client->getCollections()->create($payload);

        return $this->client->getCollections()->__get($name);
    }

    public function fillCollection(MapperInterface $mapper): \Generator
    {
        $mapping = $mapper->getMapping();
        $name = $this->getMappingName($mapping);

        $collection = $this->client->getCollections()->offsetGet($name);
        $data = $mapper->getData();
        foreach ($data as $item) {
            $collection->documents->create($item);
            yield $item;
        }
    }

    private function getMappingName(Mapper\Mapping $mapping): string
    {
        return $this->collectionPrefix.$mapping->getName();
    }
}
