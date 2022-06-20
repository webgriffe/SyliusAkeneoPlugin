<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Webgriffe\SyliusAkeneoPlugin\DependencyInjection\WebgriffeSyliusAkeneoExtension;

final class ExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function it_adds_tagged_definitions_for_product_value_handlers_friendly_configuration(): void
    {
        $this->load(
            [
                'value_handlers' => [
                    'product' => [
                        'test' => [
                            'type' => 'generic_property',
                            'priority' => 42,
                            'options' => [
                                'akeneo_attribute_code' => 'attribute',
                                'sylius_property_path' => 'property',
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'webgriffe_sylius_akeneo.value_handler.product.test_value_handler',
            'webgriffe_sylius_akeneo.product.value_handler',
            ['priority' => 42],
        );
    }

    /**
     * @test
     */
    public function it_should_register_api_client_parameters_even_if_not_defined()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.api_client.base_url', null);
        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.api_client.username', null);
        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.api_client.client_id', null);
        $this->assertContainerBuilderHasParameter('webgriffe_sylius_akeneo.api_client.secret', null);
    }

    /**
     * @test
     */
    public function it_should_not_register_any_product_value_handler_when_not_defined()
    {
        $this->load();

        self::assertEmpty($this->container->findTaggedServiceIds('webgriffe_sylius_akeneo.product.value_handler'));
    }

    protected function getContainerExtensions(): array
    {
        return [new WebgriffeSyliusAkeneoExtension()];
    }
}
