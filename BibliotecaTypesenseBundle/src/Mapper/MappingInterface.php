<?php

namespace Biblioteca\TypesenseBundle\Mapper;

interface MappingInterface
{
    /**
     * @return FieldMappingInterface[]
     */
    public function getFields(): array;

    public function getName(): string;

    public function getCollectionOptions(): ?CollectionOptionsInterface;
}
