<?php

namespace Biblioteca\TypesenseBundle\Mapper;

interface MapperInterface
{
    public function getMapping(): MappingInterface;

    /**
     * Data to index, the key is the field name
     * @return \Generator<array<string, mixed>>
     */
    public function getData(): \Generator;

    /**
     * How many data to index. If null, the progression is unknown.
     * @return int|null
     */
    public function getDataCount(): ?int;
}
