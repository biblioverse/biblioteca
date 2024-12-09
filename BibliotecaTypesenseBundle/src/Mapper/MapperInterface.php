<?php

namespace Biblioteca\TypesenseBundle\Mapper;

interface MapperInterface
{
    public function getMapping(): Mapping;

    /**
     * @return \generator<array<string, mixed>>
     */
    public function getData(): \generator;

    public function getDataCount(): ?int;
}
