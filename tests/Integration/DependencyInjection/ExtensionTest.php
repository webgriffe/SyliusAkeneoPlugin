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
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'webgriffe_sylius_akeneo.value_handler.product.test_value_handler',
            'webgriffe_sylius_akeneo.product.value_handler',
            ['priority' => 42]
        );
    }

    protected function getMinimalConfiguration(): array
    {
        return [
            'api_client' => [
                'base_url' => 'value',
                'username' => 'value',
                'password' => 'value',
                'client_id' => 'value',
                'secret' => 'value',
            ],
        ];
    }

    protected function getContainerExtensions(): array
    {
        return [new WebgriffeSyliusAkeneoExtension()];
    }
}
