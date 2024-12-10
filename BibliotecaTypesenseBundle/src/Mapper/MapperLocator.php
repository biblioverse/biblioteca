<?php

namespace Biblioteca\TypesenseBundle\Mapper;

class MapperLocator
{
    /**
     * TODO Check if we need to inject the ServiceLocator and lazy load this.
     * @param iterable<MapperInterface> $mappers
     */
    public function __construct(private readonly iterable $mappers)
    {
    }

    /**
     * @return \Generator<MapperInterface>
     */
    public function getMappers(): \Generator
    {
        foreach ($this->mappers as $mapper) {
            yield $mapper;
        }
    }

    public function count(): int
    {
        return count($this->mappers);
    }
}
