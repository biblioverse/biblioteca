<?php

namespace Biblioteca\TypesenseBundle\Mapper;

use Biblioteca\TypesenseBundle\Type\DataTypeEnum;

class Mapping implements MappingInterface
{
    public function __construct(
        private string $name,
        /** @var array<int, FieldMappingInterface> */
        private array $fields = [],
        private readonly ?CollectionOptionsInterface $collectionOptions = null)
    {
    }

    /**
     * @return FieldMappingInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(FieldMappingInterface $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    public function add(string $name, DataTypeEnum $type, ?bool $facet = null, ?bool $optional = null): self
    {
        $this->addField(new FieldMapping(name: $name, type: $type, facet: $facet, optional: $optional));

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCollectionOptions(): ?CollectionOptionsInterface
    {
        return $this->collectionOptions;
    }
}
