<?php

namespace Biblioteca\TypesenseBundle\Mapper;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Mapping
{
    public function __construct(private string $name, private array $fields = [])
    {
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setField(string $name, array $options): self
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired(['name', 'type']);
        $optionResolver->setDefined(['facet', 'optional']);

        $data = $optionResolver->resolve($options);
        unset($data['optional']);
        $this->fields[$name] = $data;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCollectionOptions(): array
    {
        return [];
    }
}
