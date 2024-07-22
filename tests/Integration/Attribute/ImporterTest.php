<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Attribute;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use DateTime;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Webgriffe\SyliusAkeneoPlugin\DataFixtures\DataFixture;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;
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
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.attribute.importer');
        $fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->attributeRepository = self::getContainer()->get('sylius.repository.product_attribute');
        $this->optionRepository = self::getContainer()->get('sylius.repository.product_option');

        /**
         * @TODO: Move this methods to a generic class on some events on PHPUnit?
         */
        InMemoryAttributeApi::clear();

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
        InMemoryAttributeApi::addResource(Attribute::create('sellable', [
            'type' => AttributeType::BOOLEAN,
            'labels' => [
                'en_US' => 'Sellable',
                'it_IT' => 'Vendibile',
            ],
        ]));

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/Attribute/' . $this->name() . '.yaml';
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
    public function it_throws_if_attribute_does_not_exists_on_akeneo(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->importer->import('not_existent_attribute');
    }

    /**
     * @test
     */
    public function it_does_nothing_if_not_exists_sylius_attribute_or_option_with_that_code(): void
    {
        $this->importer->import('text_attribute');

        $attribute = $this->attributeRepository->findOneBy(['code' => 'text_attribute']);
        $this->assertNull($attribute);

        $option = $this->optionRepository->findOneBy(['code' => 'text_attribute']);
        $this->assertNull($option);
    }

    /**
     * @test
     */
    public function it_imports_attribute_translations_from_akeneo_to_sylius(): void
    {
        $this->importer->import('size');

        $attribute = $this->attributeRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductAttributeInterface::class, $attribute);
        $this->assertEquals('Size', $attribute->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $attribute->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_updates_attribute_translations_from_akeneo_to_sylius(): void
    {
        $this->importer->import('size');

        $attribute = $this->attributeRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductAttributeInterface::class, $attribute);
        $this->assertEquals('Size', $attribute->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $attribute->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_imports_option_translations_from_akeneo_to_sylius(): void
    {
        $this->importer->import('size');

        $option = $this->optionRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Size', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $option->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_updates_option_translations_from_akeneo_to_sylius(): void
    {
        $this->importer->import('size');

        $option = $this->optionRepository->findOneBy(['code' => 'size']);
        $this->assertInstanceOf(ProductOptionInterface::class, $option);
        $this->assertEquals('Size', $option->getTranslation('en_US')->getName());
        $this->assertEquals('Taglia', $option->getTranslation('it_IT')->getName());
    }

    /**
     * @test
     */
    public function it_returns_all_option_and_attributes_identifiers_that_are_also_sylius_attributes_or_sylius_product_options(): void
    {
        InMemoryAttributeApi::addResource(Attribute::create('product_option_of_wrong_type', [
            'type' => AttributeType::TEXT,
        ]));
        InMemoryAttributeApi::addResource(Attribute::create('other_attribute', [
            'type' => AttributeType::TEXT,
        ]));

        $identifiers = $this->importer->getIdentifiersModifiedSince(new DateTime());

        $this->assertEquals(['material', 'other_attribute', 'size', 'length', 'sellable'], $identifiers);
    }
}
