<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM\QueueItemRepository;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItem;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webgriffe_sylius_akeneo');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()

                ->arrayNode('api_client')
                    ->children()
                        ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('secret')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()

                ->arrayNode('value_handlers')
                    ->children()
                        ->arrayNode('product')
                            ->arrayPrototype()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->enumNode('type')
                                        ->values(
                                            array_keys(WebgriffeSyliusAkeneoExtension::$valueHandlersTypesDefinitions)
                                        )
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

                ->arrayNode('resources')->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('queue_item')->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(QueueItem::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(QueueItemInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(QueueItemRepository::class)->cannotBeEmpty()->end()
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
