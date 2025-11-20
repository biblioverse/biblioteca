<?php

use App\Entity\Book;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $config = [
        'typesense' => [
            'uri' => '%env(resolve:TYPESENSE_URL)%',
            'key' => '%env(resolve:TYPESENSE_KEY)%',
        ],
        'collections' => [
            'books' => [
                'name' => 'books',
                'entity' => Book::class,
                'mapping' => [
                    'fields' => [
                        'id' => [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        'title' => [
                            'name' => 'title',
                            'type' => 'string',
                        ],
                        'sortable_id' => [
                            'entity_attribute' => 'id',
                            'name' => 'sortable_id',
                            'type' => 'int32',
                        ],
                        'publisher' => [
                            'name' => 'publisher',
                            'type' => 'string',
                            'optional' => true,
                            'facet' => true,
                        ],
                        'serie' => [
                            'name' => 'serie',
                            'type' => 'string',
                            'optional' => true,
                            'facet' => true,
                        ],
                        'summary' => [
                            'name' => 'summary',
                            'type' => 'string',
                            'optional' => true,
                        ],
                        'serieIndex' => [
                            'name' => 'serieIndex',
                            'type' => 'float',
                            'optional' => true,
                        ],
                        'extension' => [
                            'name' => 'extension',
                            'type' => 'string',
                            'facet' => true,
                        ],
                        'authors' => [
                            'name' => 'authors',
                            'type' => 'string[]',
                            'facet' => true,
                        ],
                        'verified' => [
                            'name' => 'verified',
                            'type' => 'bool',
                            'facet' => true,
                        ],
                        'tags' => [
                            'name' => 'tags',
                            'type' => 'string[]',
                            'facet' => true,
                            'optional' => true,
                        ],
                        'tags_empty' => [
                            'name' => 'tags_empty',
                            'type' => 'bool',
                            'entity_attribute' => 'isTagsEmpty',
                        ],
                        'summary_empty' => [
                            'name' => 'summary_empty',
                            'type' => 'bool',
                            'entity_attribute' => 'isSummaryEmpty',
                        ],
                        'updated' => [
                            'name' => 'updated',
                            'type' => 'int64',
                        ],
                        'age' => [
                            'name' => 'age',
                            'type' => 'string',
                            'facet' => true,
                            'optional' => true,
                            'entity_attribute' => 'ageCategoryLabel',
                        ],
                        'book_path' => [
                            'name' => 'book_path',
                            'type' => 'string',
                            'optional' => true,
                            'entity_attribute' => 'bookPath',
                        ],
                        'user.read' => [
                            'name' => 'read',
                            'optional' => true,
                            'type' => 'int32[]',
                            'entity_attribute' => 'users.read',
                        ],
                        'user.hidden' => [
                            'name' => 'hidden',
                            'optional' => true,
                            'type' => 'int32[]',
                            'entity_attribute' => 'users.hidden',
                        ],
                        'user.favorite' => [
                            'name' => 'favorite',
                            'optional' => true,
                            'type' => 'int32[]',
                            'entity_attribute' => 'users.favorite',
                        ],
                    ],
                    'default_sorting_field' => 'sortable_id',
                    'symbols_to_index' => ['+', '#', '@', '_'],
                    'token_separators' => [' ', '-', "'"],
                ],
            ],
        ],
    ];

    // Conditional embedding
    // We check if the required environment variables are present and not empty
    // We use getenv() as a fallback because $_ENV might not be populated depending on variables_order in php.ini
    $embedModel = $_ENV['TYPESENSE_EMBED_MODEL'] ?? $_SERVER['TYPESENSE_EMBED_MODEL'] ?? getenv('TYPESENSE_EMBED_MODEL') ?: null;

    if ($embedModel) {
        $config['collections']['books']['mapping']['fields']['embedding'] = [
            'name' => 'embedding',
            'type' => 'float[]',
            'index' => true,
            'mapped' => false,
            'numDim' => '%TYPESENSE_EMBED_NUM_DIM%',
            'embed' => [
                'from' => ['extension', 'tags', 'summary'],
                'model_config' => [
                    'model_name' => '%TYPESENSE_EMBED_MODEL%',
                    'api_key' => '%TYPESENSE_EMBED_KEY%',
                    'url' => '%TYPESENSE_EMBED_URL%',
                ],
            ],
        ];
    }

    $containerConfigurator->extension('biblioverse_typesense', $config);

    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('biblioverse_typesense', [
            'auto_update' => false,
        ]);
    }
};
