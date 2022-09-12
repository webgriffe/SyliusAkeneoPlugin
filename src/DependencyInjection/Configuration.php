<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webgriffe_sylius_akeneo_plugin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()

                ->arrayNode('api_client')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->defaultNull()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->defaultNull()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->defaultNull()->end()
                        ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->defaultNull()->end()
                        ->scalarNode('secret')->isRequired()->cannotBeEmpty()->defaultNull()->end()
                    ->end()
                ->end()

                ->arrayNode('value_handlers')
                    ->children()
                        ->arrayNode('product')
                            ->arrayPrototype()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->enumNode('type')
                                        ->values(WebgriffeSyliusAkeneoExtension::getAllowedValueHandlersTypes())
                                        ->isRequired()
                                    ->end()
                                    ->arrayNode('options')
                                        ->variablePrototype()
                                        ->end()
                                    ->end()
                                    ->integerNode('priority')
                                        ->defaultValue(0)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}
