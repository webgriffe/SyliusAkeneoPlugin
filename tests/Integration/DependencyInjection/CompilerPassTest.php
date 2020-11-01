<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Webgriffe\SyliusAkeneoPlugin\DependencyInjection\WebgriffeSyliusAkeneoExtension;
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
            $valueHandlersResolverDefinition
        );
        $taggedValueHandlerDefinition = new Definition(
            GenericPropertyValueHandler::class,
            [
                new Reference('property_accessor'),
                'akeneo_attribute',
                'sylius_property',
            ]
        );
        $taggedValueHandlerDefinition->addTag('webgriffe_sylius_akeneo.product.value_handler', ['priority' => 42]);
        $this->container->setDefinition('app.my.custom.value_handler', $taggedValueHandlerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasService('webgriffe_sylius_akeneo.product.value_handlers_resolver');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'webgriffe_sylius_akeneo.product.value_handlers_resolver',
            'add',
            [new Reference('app.my.custom.value_handler'), 42]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new WebgriffeSyliusAkeneoExtension());
    }
}
