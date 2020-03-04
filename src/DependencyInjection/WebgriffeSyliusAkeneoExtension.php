<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler;

final class WebgriffeSyliusAkeneoExtension extends AbstractResourceExtension
{
    /** @var array */
    public static $valueHandlersTypesDefinitions = [
        'channel_pricing' => [
            'class' => ChannelPricingValueHandler::class,
            'arguments' => [
                'sylius.factory.channel_pricing',
                'sylius.repository.channel',
                'sylius.repository.currency',
            ],
        ],
        'generic_property' => [
            'class' => GenericPropertyValueHandler::class,
            'arguments' => [
                'property_accessor',
            ],
        ],
        'image' => [
            'class' => ImageValueHandler::class,
            'arguments' => [
                'sylius.factory.product_image',
                'sylius.repository.product_image',
                'webgriffe_sylius_akeneo.api_client',
            ],
        ],
        'immutable_slug' => [
            'class' => ImmutableSlugValueHandler::class,
            'arguments' => [
                'sonata.core.slugify.cocur',
                'sylius.factory.product_translation',
                'sylius.translation_locale_provider.admin',
                'sylius.repository.product_translation',
            ],
        ],
        'product_option' => [
            'class' => ProductOptionValueHandler::class,
            'arguments' => [
                'webgriffe_sylius_akeneo.api_client',
                'sylius.repository.product_option',
                'sylius.factory.product_option_value',
                'sylius.factory.product_option_value_translation',
                'sylius.repository.product_option_value',
            ],
        ],
        'translatable_property' => [
            'class' => TranslatablePropertyValueHandler::class,
            'arguments' => [
                'property_accessor',
                'sylius.factory.product_translation',
                'sylius.factory.product_variant_translation',
                'sylius.translation_locale_provider.admin',
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->registerResources('webgriffe_sylius_akeneo_plugin', 'doctrine/orm', $config['resources'], $container);
        $this->registerApiClientParameters($config['api_client'], $container);

        $loader->load('services.xml');

        $productValueHandlersDefinitions = $this->createValueHandlersDefinitions($config['value_handlers']['product']);
        $container->addDefinitions($productValueHandlersDefinitions);

        if ($container->hasDefinition('webgriffe_sylius_akeneo.product.value_handlers_resolver')) {
            $resolverDefinition = $container->getDefinition('webgriffe_sylius_akeneo.product.value_handlers_resolver');
            foreach (array_keys($productValueHandlersDefinitions) as $reference) {
                $resolverDefinition->addMethodCall('add', [new Reference($reference)]);
            }
        }
    }

    /**
     * @return Definition[]
     */
    private function createValueHandlersDefinitions(array $valueHandlers): array
    {
        $definitions = [];
        foreach ($valueHandlers as $name => $valueHandler) {
            $type = $valueHandler['type'];
            $options = $valueHandler['options'] ?? [];

            $arguments = array_merge(
                array_map(
                    function (string $argument) {
                        return new Reference($argument);
                    },
                    self::$valueHandlersTypesDefinitions[$type]['arguments']
                ),
                array_values($options)
            );
            $id = sprintf('webgriffe_sylius_akeneo.value_handler.product.%s_value_handler', $name);
            $definitions[$id] = new Definition(self::$valueHandlersTypesDefinitions[$type]['class'], $arguments);
        }

        return $definitions;
    }

    private function registerApiClientParameters(array $apiClient, ContainerBuilder $container): void
    {
        foreach ($apiClient as $key => $value) {
            $container->setParameter(sprintf('webgriffe_sylius_akeneo.api_client.%s', $key), $value);
        }
    }
}
