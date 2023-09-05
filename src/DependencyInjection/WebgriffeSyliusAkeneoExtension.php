<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;
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
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\MetricPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler;
use Webmozart\Assert\Assert;

final class WebgriffeSyliusAkeneoExtension extends AbstractResourceExtension implements CompilerPassInterface
{
    private const PRODUCT_VALUE_HANDLER_TAG = 'webgriffe_sylius_akeneo.product.value_handler';

    private const IMPORTER_TAG = 'webgriffe_sylius_akeneo.importer';

    private const RECONCILER_TAG = 'webgriffe_sylius_akeneo.reconciler';

    /** @var array<string, array{class: string, arguments: string[]}> */
    private static array $valueHandlersTypesDefinitionsPrivate = [
        'channel_pricing' => [
            'class' => ChannelPricingValueHandler::class,
            'arguments' => [
                '$channelPricingFactory' => 'sylius.factory.channel_pricing',
                '$channelRepository' => 'sylius.repository.channel',
                '$currencyRepository' => 'sylius.repository.currency',
                '$propertyAccessor' => 'property_accessor',
            ],
        ],
        'generic_property' => [
            'class' => GenericPropertyValueHandler::class,
            'arguments' => [
                '$propertyAccessor' => 'property_accessor',
            ],
        ],
        'image' => [
            'class' => ImageValueHandler::class,
            'arguments' => [
                '$productImageFactory' => 'sylius.factory.product_image',
                '$productImageRepository' => 'sylius.repository.product_image',
                '$apiClient' => 'webgriffe_sylius_akeneo.api_client',
                '$temporaryFilesManager' => 'webgriffe_sylius_akeneo.temporary_file_manager',
            ],
        ],
        'immutable_slug' => [
            'class' => ImmutableSlugValueHandler::class,
            'arguments' => [
                '$slugify' => 'webgriffe_sylius_akeneo.slugify',
                '$productTranslationFactory' => 'sylius.factory.product_translation',
                '$translationLocaleProvider' => 'sylius.translation_locale_provider.admin',
                '$productTranslationRepository' => 'sylius.repository.product_translation',
            ],
        ],
        'product_option' => [
            'class' => ProductOptionValueHandler::class,
            'arguments' => [
                '$apiClient' => 'webgriffe_sylius_akeneo.api_client',
                '$productOptionRepository' => 'sylius.repository.product_option',
                '$productOptionValueFactory' => 'sylius.factory.product_option_value',
                '$productOptionValueTranslationFactory' => 'sylius.factory.product_option_value_translation',
                '$productOptionValueRepository' => 'sylius.repository.product_option_value',
                '$translationLocaleProvider' => 'sylius.translation_locale_provider.admin',
                '$translator' => 'translator',
            ],
        ],
        'translatable_property' => [
            'class' => TranslatablePropertyValueHandler::class,
            'arguments' => [
                '$propertyAccessor' => 'property_accessor',
                '$productTranslationFactory' => 'sylius.factory.product_translation',
                '$productVariantTranslationFactory' => 'sylius.factory.product_variant_translation',
                '$localeProvider' => 'sylius.translation_locale_provider.admin',
            ],
        ],
        'generic_attribute' => [
            'class' => AttributeValueHandler::class,
            'arguments' => [
                '$attributeRepository' => 'sylius.repository.product_attribute',
                '$factory' => 'sylius.factory.product_attribute_value',
                '$localeProvider' => 'sylius.translation_locale_provider.admin',
                '$valueConverter' => 'webgriffe_sylius_akeneo.converter.value',
            ],
        ],
        'file_attribute' => [
            'class' => FileAttributeValueHandler::class,
            'arguments' => [
                '$apiClient' => 'webgriffe_sylius_akeneo.api_client',
                '$filesystem' => 'filesystem',
                '$temporaryFilesManager' => 'webgriffe_sylius_akeneo.temporary_file_manager',
            ],
        ],
        'metric_property' => [
            'class' => MetricPropertyValueHandler::class,
            'arguments' => [
                '$propertyAccessor' => 'property_accessor',
                '$unitMeasurementValueConverter' => 'webgriffe_sylius_akeneo.converter.unit_measurement_value',
            ],
        ],
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        Assert::isArray($config['resources']);
        $this->registerResources('webgriffe_sylius_akeneo', 'doctrine/orm', $config['resources'], $container);

        $this->registerApiClientParameters($config['api_client'], $container);

        $loader->load('services.xml');

        $container->addDefinitions(
            $this->createValueHandlersDefinitionsAndPriorities($config['value_handlers']['product'] ?? []),
        );
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    public function process(ContainerBuilder $container): void
    {
        $this->addTaggedValueHandlersToResolver($container);
        $this->addTaggedImportersToRegistry($container);
        $this->addTaggedReconcilersToRegistry($container);
        $this->registerTemporaryDirectoryParameter($container);
    }

    /** @return string[] */
    public static function getAllowedValueHandlersTypes(): array
    {
        return array_keys(self::$valueHandlersTypesDefinitionsPrivate);
    }

    /** @return array<string, Definition> */
    private function createValueHandlersDefinitionsAndPriorities(array $valueHandlers): array
    {
        /** @var array<string, Definition> $definitions */
        $definitions = [];
        foreach ($valueHandlers as $key => $valueHandler) {
            $type = $valueHandler['type'];
            Assert::string($type);
            /** @var array<string, string> $options */
            $options = $valueHandler['options'] ?? [];
            $priority = $valueHandler['priority'] ?? 0;

            if ($type === 'channel_pricing' && (!array_key_exists('$akeneoAttribute', $options))) {
                /** @var array<string, string> $optionsNamed */
                $optionsNamed = [];
                $optionsNamed['$akeneoAttribute'] = array_shift($options);
                if (count($options) > 0) {
                    $optionsNamed['$syliusPropertyPath'] = array_shift($options);
                }
                $options = $optionsNamed;
            }

            $arguments = array_merge(
                array_map(
                    static fn (string $argumentValue): Reference => new Reference($argumentValue),
                    self::$valueHandlersTypesDefinitionsPrivate[$type]['arguments'],
                ),
                $options,
            );
            $id = sprintf('webgriffe_sylius_akeneo.value_handler.product.%s_value_handler', $key);
            $definition = new Definition(self::$valueHandlersTypesDefinitionsPrivate[$type]['class'], $arguments);
            $definition->addTag(self::PRODUCT_VALUE_HANDLER_TAG, ['priority' => $priority]);
            $definitions[$id] = $definition;
        }

        return $definitions;
    }

    /** @param array<array-key, string|null> $apiClient */
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
            'webgriffe_sylius_akeneo.product.value_handlers_resolver',
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
        foreach ($taggedImporters as $id => $_tags) {
            $importerRegistryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }

    private function addTaggedReconcilersToRegistry(ContainerBuilder $container): void
    {
        if (!$container->has('webgriffe_sylius_akeneo.reconciler_registry')) {
            return;
        }

        $importerRegistryDefinition = $container->findDefinition('webgriffe_sylius_akeneo.reconciler_registry');

        $taggedReconcilers = $container->findTaggedServiceIds(self::RECONCILER_TAG);
        foreach ($taggedReconcilers as $id => $_tags) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $importerRegistryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }

    private function registerTemporaryDirectoryParameter(ContainerBuilder $container): void
    {
        $parameterKey = 'webgriffe_sylius_akeneo.temporary_directory';
        if ($container->hasParameter($parameterKey)) {
            return;
        }
        $container->setParameter($parameterKey, sys_get_temp_dir());
    }
}
