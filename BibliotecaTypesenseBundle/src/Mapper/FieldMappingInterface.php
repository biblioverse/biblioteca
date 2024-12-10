<?php

namespace Biblioteca\TypesenseBundle\Mapper;

use Biblioteca\TypesenseBundle\Type\DataTypeEnum;

interface FieldMappingInterface
{
    /**
     * Field options and value
     * @return array<string,mixed>
     */
    public function toArray(): array;

    /**
     * @see DataTypeEnum
     * @return string
     */
    public function getType(): string;

    public function getName(): string;
}
