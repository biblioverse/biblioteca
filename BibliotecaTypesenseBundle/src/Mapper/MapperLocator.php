<?php

namespace Biblioteca\TypesenseBundle\Mapper;

class MapperLocator
{
    /**
     * @param iterable<MapperInterface> $mappers
     */
    public function __construct(private iterable $mappers)
    {
    }

    /**
     * @return \generator<MapperInterface>
     */
    public function getMappers(): \Generator
    {
        foreach ($this->mappers as $mapper) {
            yield $mapper;
        }
    }

    public function addMapper(MapperInterface $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function count(): int
    {
        return count($this->mappers);
    }
}
