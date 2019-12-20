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
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webgriffe_sylius_akeneo_plugin');
        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('webgriffe_sylius_akeneo_plugin');
        }

        $rootNode
            ->children()
                ->arrayNode('resources')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('queue_item')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->variableNode('options')->end()
                            ->arrayNode('classes')
                                ->addDefaultsIfNotSet()
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
        ;

        return $treeBuilder;
    }
}
