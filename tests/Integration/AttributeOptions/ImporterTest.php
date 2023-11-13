<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\AttributeOptions;

use DateTime;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Product\Model\ProductAttributeInterface;
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

    private PurgerLoader $fixtureLoader;

    private RepositoryInterface $attributeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.attribute_options.importer');
        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->attributeRepository = self::getContainer()->get('sylius.repository.product_attribute');

        InMemoryAttributeApi::addResource(new Attribute('material', AttributeType::SIMPLE_SELECT));
        InMemoryAttributeApi::addResource(new Attribute('text_attribute', AttributeType::TEXT));

        InMemoryAttributeOptionApi::addResource(new AttributeOption('metal', 'material', 1, [
            'en_US' => 'metal',
            'it_IT' => 'metallo',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('leather', 'material', 2, [
            'en_US' => 'leather',
            'it_IT' => 'cuoio',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('polyester', 'material', 3, [
            'en_US' => 'polyester',
            'it_IT' => 'poliestere',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('wool', 'material', 4, [
            'en_US' => 'wool',
            'it_IT' => 'lana',
        ]));
        InMemoryAttributeOptionApi::addResource(new AttributeOption('cotton', 'material', 5, [
            'en_US' => 'cotton',
            'it_IT' => 'cotone',
        ]));

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/AttributeOptions/' . $this->getName() . '.yaml';
        if (file_exists($ORMResourceFixturePath)) {
            $this->fixtureLoader->load(
                [$ORMResourceFixturePath],
                [],
                [],
                PurgeMode::createDeleteMode(),
            );
        } else {
            $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
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
    public function it_import_all_options_from_akeneo_retaining_sort_order(): void
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
    public function it_returns_all_simple_select_and_multiselect_attributes_identifiers_that_are_also_sylius_select_attributes(): void
    {
        $identifiers = $this->importer->getIdentifiersModifiedSince(new DateTime());

        $this->assertEquals(['material'], $identifiers);
    }
}
