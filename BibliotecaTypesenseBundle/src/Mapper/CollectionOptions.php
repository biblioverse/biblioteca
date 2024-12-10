<?php

namespace Biblioteca\TypesenseBundle\Mapper;

class CollectionOptions implements CollectionOptionsInterface
{
    public function __construct(
        public ?string $tokenSeparators = null,
        public ?string $symbolsToIndex = null,
        public ?string $defaultSortingField = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'token_separators' => $this->tokenSeparators,
            'symbols_to_index' => $this->symbolsToIndex,
            'default_sorting_field' => $this->defaultSortingField,
        ];
    }
}
