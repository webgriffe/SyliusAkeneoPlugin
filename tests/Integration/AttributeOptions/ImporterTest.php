<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\AttributeOptions;

use DateTime;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Webgriffe\SyliusAkeneoPlugin\DataFixtures\DataFixture;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeOptionApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeOption;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeType;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    private ImporterInterface $importer;

    private RepositoryInterface $attributeRepository;

    private ProductOptionRepositoryInterface $optionRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.attribute_options.importer');
        $fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->attributeRepository = self::getContainer()->get('sylius.repository.product_attribute');
        $this->optionRepository = self::getContainer()->get('sylius.repository.product_option');

        InMemoryAttributeApi::addResource(new Attribute('material', AttributeType::SIMPLE_SELECT));
        InMemoryAttributeApi::addResource(new Attribute('text_attribute', AttributeType::TEXT));
        InMemoryAttributeApi::addResource(Attribute::create('size', [
            'type' => AttributeType::SIMPLE_SELECT,
            'labels' => [
                'en_US' => 'Size',
                'it_IT' => 'Taglia',
            ],
        ]));
        InMemoryAttributeApi::addResource(Attribute::create('length', [
            'type' => AttributeType::METRIC,
            'metric_family' => 'Length',
            'default_metric_unit' => 'CENTIMETER',
            'labels' => [
                'en_US' => 'Length',
                'it_IT' => 'Lunghezza',
            ],
        ]));

        InMemoryAttributeOptionApi::addResource(new AttributeOption('cotton', 'material', 5, [
            'en_US' => 'cotton',
            'it_IT' => 'cotone',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('polyester', 'material', 3, [
            'en_US' => 'polyester',
            'it_IT' => 'poliestere',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('metal', 'material', 1, [
            'en_US' => 'metal',
            'it_IT' => 'metallo',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('wool', 'material', 4, [
            'en_US' => 'wool',
            'it_IT' => 'lana',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('leather', 'material', 2, [
            'en_US' => 'leather',
            'it_IT' => 'cuoio',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('small', 'size', 1, [
            'en_US' => 'Small',
            'it_IT' => 'Piccola',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('large', 'size', 2, [
            'en_US' => 'Large',
            'it_IT' => 'Grande',
        ]));

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/AttributeOptions/' . $this->getName() . '.yaml';
        if (file_exists($ORMResourceFixturePath)) {
            $fixtureLoader->load(
                [$ORMResourceFixturePath],
                [],
                [],
                PurgeMode::createDeleteMode(),
            );
        } else {
            $fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
        }
    }

    /**
     * @test
     */
    public function it_does_nothing_if_attribute_does_not_exists_on_sylius(): void
    {
        $this->importer->import('not_existent_attribute');

        $this->assertCount(0, $this->attributeRepository->findAll());
    }

    /**
     * @test
     */
    public function it_does_nothing_if_attribute_is_not_a_select_attribute(): void
    {
        $this->importer->import('text_attribute');

        $attribute = $this->attributeRepository->findOneBy(['code' => 'text_attribute']);
        $this->assertInstanceOf(ProductAttributeInterface::class, $attribute);
        $this->assertEmpty($attribute->getConfiguration());
    }

    /**
     * @test
     */
    public function it_import_all_options_from_akeneo_to_sylius_attribute_retaining_sort_order(): void
    {
        $this->importer->import('material');

        $attribute = $this->attributeRepository->findOneBy(['code' => 'material']);
        $this->assertInstanceOf(ProductAttributeInterface::class, $attribute);
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
    public function it_import_all_select_attribute_options_from_akeneo_to_sylius_option(): void
    {
        $this->importer->import('size');

        $option = $this->optionRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Size', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $option->getTranslation('it_IT')->getName());
        $optionValues = $option->getValues();
        $this->assertCount(2, $optionValues);

        $smallOptionValues = $optionValues->filter(static fn (ProductOptionValueInterface $optionValue): bool => $optionValue->getCode() === 'size_small');
        $this->assertCount(1, $smallOptionValues);
        $smallOptionValue = $smallOptionValues->first();
        $this->assertInstanceOf(ProductOptionValueInterface::class, $smallOptionValue);
        $this->assertCount(2, $smallOptionValue->getTranslations());
        $this->assertEquals('Small', $smallOptionValue->getTranslation('en_US')->getValue());
        $this->assertEquals('Piccola', $smallOptionValue->getTranslation('it_IT')->getValue());

        $smallOptionValues = $optionValues->filter(static fn (ProductOptionValueInterface $optionValue): bool => $optionValue->getCode() === 'size_large');
        $this->assertCount(1, $smallOptionValues);
        $smallOptionValue = $smallOptionValues->first();
        $this->assertInstanceOf(ProductOptionValueInterface::class, $smallOptionValue);
        $this->assertCount(2, $smallOptionValue->getTranslations());
        $this->assertEquals('Large', $smallOptionValue->getTranslation('en_US')->getValue());
        $this->assertEquals('Grande', $smallOptionValue->getTranslation('it_IT')->getValue());
    }

    /**
     * @test
     */
    public function it_updates_all_select_attribute_options_from_akeneo_to_sylius_option(): void
    {
        $this->importer->import('size');

        $option = $this->optionRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Size', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $option->getTranslation('it_IT')->getName());
        $optionValues = $option->getValues();
        $this->assertCount(2, $optionValues);

        $smallOptionValues = $optionValues->filter(static fn (ProductOptionValueInterface $optionValue): bool => $optionValue->getCode() === 'size_small');
        $this->assertCount(1, $smallOptionValues);
        $smallOptionValue = $smallOptionValues->first();
        $this->assertInstanceOf(ProductOptionValueInterface::class, $smallOptionValue);
        $this->assertCount(2, $smallOptionValue->getTranslations());
        $this->assertEquals('Small', $smallOptionValue->getTranslation('en_US')->getValue());
        $this->assertEquals('Piccola', $smallOptionValue->getTranslation('it_IT')->getValue());

        $smallOptionValues = $optionValues->filter(static fn (ProductOptionValueInterface $optionValue): bool => $optionValue->getCode() === 'size_large');
        $this->assertCount(1, $smallOptionValues);
        $smallOptionValue = $smallOptionValues->first();
        $this->assertInstanceOf(ProductOptionValueInterface::class, $smallOptionValue);
        $this->assertCount(2, $smallOptionValue->getTranslations());
        $this->assertEquals('Large', $smallOptionValue->getTranslation('en_US')->getValue());
        $this->assertEquals('Grande', $smallOptionValue->getTranslation('it_IT')->getValue());
    }

    /**
     * @test
     */
    public function it_imports_metric_attribute_labels_from_akeneo_to_sylius_option_translation(): void
    {
        $this->importer->import('length');

        $option = $this->optionRepository->findOneBy(['code' => 'length']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Length', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Lunghezza', $option->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_updates_metric_attribute_labels_from_akeneo_to_sylius_option_translation(): void
    {
        $this->importer->import('length');

        $option = $this->optionRepository->findOneBy(['code' => 'length']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Length', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Lunghezza', $option->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_returns_all_metric_simple_select_and_multiselect_attributes_identifiers_that_are_also_sylius_select_attributes_or_sylius_product_options(): void
    {
        $identifiers = $this->importer->getIdentifiersModifiedSince(new DateTime());

        $this->assertEquals(['material', 'size', 'length'], $identifiers);
    }
}
