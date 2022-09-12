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
    private ImporterInterface $importer;

    private PurgerLoader $fixtureLoader;

    private RepositoryInterface $attributeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.attribute_options.importer');
        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->attributeRepository = self::getContainer()->get('sylius.repository.product_attribute');
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
            PurgeMode::createDeleteMode(),
        );

        $this->importer->import('text_attribute');

        /** @var ProductAttributeInterface $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => 'text_attribute']);
        $this->assertEmpty($attribute->getConfiguration());
    }

    /**
     * @test
     */
    public function it_import_all_options_from_akeneo_retaining_sort_order(): void
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAttribute/material.yaml',
            ],
        );

        $this->importer->import('material');

        /** @var ProductAttributeInterface $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => 'material']);
        $configuration = $attribute->getConfiguration();
        $this->assertCount(5, $configuration['choices']);
        $this->assertSame(
            [
                'metal' => ['en_US' => 'metal', 'it_IT' => 'metallo'],
                'leather' => ['en_US' => 'leather', 'it_IT' => 'cuoio'],
                'polyester' => ['en_US' => 'polyester', 'it_IT' => 'poliestere'],
                'wool' => ['en_US' => 'wool', 'it_IT' => 'lana'],
                'cotton' => ['en_US' => 'cotton', 'it_IT' => 'cotone'],
            ],
            $configuration['choices'],
        );
    }

    /**
     * @test
     */
    public function it_returns_all_simple_select_and_multiselect_attributes_identifiers_that_are_also_sylius_select_attributes(): void
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAttribute/material.yaml',
            ],
        );

        $identifiers = $this->importer->getIdentifiersModifiedSince(new \DateTime());

        $this->assertEquals(['material'], $identifiers);
    }
}
