<?php

namespace Biblioteca\TypesenseBundle;

use Biblioteca\TypesenseBundle\Mapper\MapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class BibliotecaTypesenseBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('typesense')
                ->info('Typesense server configuration')
                ->isRequired()
                ->children()
                    ->scalarNode('uri')
                        ->info('The URL of the Typesense server')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('key')
                        ->info('The API key for accessing the Typesense server')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('connection_timeout_seconds')
                        ->defaultValue(5)
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        foreach ($config['typesense'] as $key => $value) {
            $container->parameters()->set('biblioteca_typesense.config.'.$key, $value);
        }

        $container->services()
            ->instanceof(MapperInterface::class)
            ->tag('typesense.mapper')
            ->autowire();

        $container->import(__DIR__.'/Resources/config/services.yaml');
    }
}
