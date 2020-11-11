<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\AttributeOptions;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    /** @var ImporterInterface */
    private $importer;

    /** @var PurgerLoader */
    private $fixtureLoader;

    /** @var RepositoryInterface */
    private $attributeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.attribute_options.importer');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->attributeRepository = self::$container->get('sylius.repository.product_attribute');
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
    }

    /**
     * @test
     */
    public function it_does_nothing_if_attribute_does_not_exists_on_sylius(): void
    {
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());

        $this->importer->import('not_existent_attribute');

        $this->assertCount(0, $this->attributeRepository->findAll());
    }

    /**
     * @test
     */
    public function it_does_nothing_if_attribute_is_not_a_select_attribute(): void
    {
        $this->fixtureLoader->load(
            [__DIR__ . '/../DataFixtures/ORM/resources/ProductAttribute/text_attribute.yaml'],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('text_attribute');

        /** @var ProductAttributeInterface $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => 'text_attribute']);
        $this->assertEmpty($attribute->getConfiguration());
    }

    /**
     * @test
     */
    public function it_import_all_options_from_akeneo(): void
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAttribute/material.yaml',
            ]
        );

        $this->importer->import('material');

        /** @var ProductAttributeInterface $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => 'material']);
        $configuration = $attribute->getConfiguration();
        $this->assertCount(5, $configuration['choices']);
        $this->assertEquals(
            [
                'cotton' => ['it_IT' => 'cotone', 'en_US' => 'cotton'],
                'leather' => ['it_IT' => 'cuoio', 'en_US' => 'leather'],
                'metal' => ['it_IT' => 'metallo', 'en_US' => 'metal'],
                'polyester' => ['it_IT' => 'poliestere', 'en_US' => 'polyester'],
                'wool' => ['it_IT' => 'lana', 'en_US' => 'wool'],
            ],
            $configuration['choices']
        );
    }
}
