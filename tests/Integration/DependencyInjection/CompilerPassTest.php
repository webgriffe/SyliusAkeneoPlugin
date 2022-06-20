<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Webgriffe\SyliusAkeneoPlugin\DependencyInjection\WebgriffeSyliusAkeneoExtension;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler;

final class CompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function adds_tagged_value_handlers_to_resolver(): void
    {
        $valueHandlersResolverDefinition = new Definition();
        $this->setDefinition(
            'webgriffe_sylius_akeneo.product.value_handlers_resolver',
            $valueHandlersResolverDefinition,
        );
        $taggedValueHandlerDefinition = new Definition(
            GenericPropertyValueHandler::class,
            [
                new Reference('property_accessor'),
                'akeneo_attribute',
                'sylius_property',
            ],
        );
        $taggedValueHandlerDefinition->addTag('webgriffe_sylius_akeneo.product.value_handler', ['priority' => 42]);
        $this->container->setDefinition('app.my.custom.value_handler', $taggedValueHandlerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasService('webgriffe_sylius_akeneo.product.value_handlers_resolver');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'webgriffe_sylius_akeneo.product.value_handlers_resolver',
            'add',
            [new Reference('app.my.custom.value_handler'), 42],
        );
    }

    /**
     * @test
     */
    public function adds_tagged_importers_to_registry(): void
    {
        $importerRegistryDefinition = new Definition();
        $this->setDefinition('webgriffe_sylius_akeneo.importer_registry', $importerRegistryDefinition);
        $taggedImporterDefinition = new Definition(Importer::class);
        $taggedImporterDefinition->addTag('webgriffe_sylius_akeneo.importer');
        $this->setDefinition('app.my.custom.importer', $taggedImporterDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'webgriffe_sylius_akeneo.importer_registry',
            'add',
            [new Reference('app.my.custom.importer')],
        );
    }

    /**
     * @test
     */
    public function registers_temporary_directory_parameter(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.temporary_directory', sys_get_temp_dir());
    }

    /**
     * @test
     */
    public function does_not_register_temporary_directory_parameter_if_it_is_already_defined(): void
    {
        $this->container->setParameter('webgriffe_sylius_akeneo.temporary_directory', '/tmp/my-custom-path');

        $this->compile();

        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.temporary_directory', '/tmp/my-custom-path');
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new WebgriffeSyliusAkeneoExtension());
    }
}
