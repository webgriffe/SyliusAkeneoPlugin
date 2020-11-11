<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler;

final class WebgriffeSyliusAkeneoExtension extends AbstractResourceExtension implements CompilerPassInterface
{
    private const PRODUCT_VALUE_HANDLER_TAG = 'webgriffe_sylius_akeneo.product.value_handler';

    private const IMPORTER_TAG = 'webgriffe_sylius_akeneo.importer';

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
                'webgriffe_sylius_akeneo.slugify',
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
        'generic_attribute' => [
            'class' => AttributeValueHandler::class,
            'arguments' => [
                'sylius.repository.product_attribute',
                'sylius.factory.product_attribute_value',
                'sylius.translation_locale_provider.admin',
            ],
        ],
        'file_attribute' => [
            'class' => FileAttributeValueHandler::class,
            'arguments' => [
                'webgriffe_sylius_akeneo.api_client',
                'filesystem',
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

        $container->addDefinitions(
            $this->createValueHandlersDefinitionsAndPriorities($config['value_handlers']['product'] ?? [])
        );
    }

    public function process(ContainerBuilder $container): void
    {
        $this->addTaggedValueHandlersToResolver($container);
        $this->addTaggedImportersToRegistry($container);
    }

    private function createValueHandlersDefinitionsAndPriorities(array $valueHandlers): array
    {
        $definitions = [];
        foreach ($valueHandlers as $key => $valueHandler) {
            $type = $valueHandler['type'];
            $options = $valueHandler['options'] ?? [];
            $priority = $valueHandler['priority'] ?? 0;

            $arguments = array_merge(
                array_map(
                    static function (string $argument): Reference {
                        return new Reference($argument);
                    },
                    self::$valueHandlersTypesDefinitions[$type]['arguments']
                ),
                array_values($options)
            );
            $id = sprintf('webgriffe_sylius_akeneo.value_handler.product.%s_value_handler', $key);
            $definition = new Definition(self::$valueHandlersTypesDefinitions[$type]['class'], $arguments);
            $definition->addTag(self::PRODUCT_VALUE_HANDLER_TAG, ['priority' => $priority]);
            $definitions[$id] = $definition;
        }

        return $definitions;
    }

    private function registerApiClientParameters(array $apiClient, ContainerBuilder $container): void
    {
        foreach ($apiClient as $key => $value) {
            $container->setParameter(sprintf('webgriffe_sylius_akeneo.api_client.%s', $key), $value);
        }
    }

    private function addTaggedValueHandlersToResolver(ContainerBuilder $container): void
    {
        if (!$container->has('webgriffe_sylius_akeneo.product.value_handlers_resolver')) {
            return;
        }

        $valueHandlersResolverDefinition = $container->findDefinition(
            'webgriffe_sylius_akeneo.product.value_handlers_resolver'
        );

        $taggedValueHandlers = $container->findTaggedServiceIds(self::PRODUCT_VALUE_HANDLER_TAG);
        foreach ($taggedValueHandlers as $id => $tags) {
            // a service could have the same tag twice
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $valueHandlersResolverDefinition->addMethodCall('add', [new Reference($id), $priority]);
            }
        }
    }

    private function addTaggedImportersToRegistry(ContainerBuilder $container): void
    {
        if (!$container->has('webgriffe_sylius_akeneo.importer_registry')) {
            return;
        }

        $importerRegistryDefinition = $container->findDefinition('webgriffe_sylius_akeneo.importer_registry');

        $taggedImporters = $container->findTaggedServiceIds(self::IMPORTER_TAG);
        foreach ($taggedImporters as $id => $tags) {
            $importerRegistryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
