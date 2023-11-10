<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\ProductAssociations;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use RuntimeException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;
use Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DataFixtures\DataFixture;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    private const MUG_DW_PRODUCT_CODE = 'MUG_DW';

    private const MUG_SW_PRODUCT_CODE = 'MUG_SW';

    private const UPSELL_ASSOCIATION_CODE = 'UPSELL';

    private ImporterInterface $importer;

    private PurgerLoader $fixtureLoader;

    private ProductRepositoryInterface $productRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.product_associations.importer');
        $this->productRepository = self::getContainer()->get('sylius.repository.product');
        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');

        InMemoryProductApi::addResource(Product::create(self::MUG_DW_PRODUCT_CODE, [
            'family' => 'mugs',
            'parent' => null,
            'values' => [],
            'associations' => [
                self::UPSELL_ASSOCIATION_CODE => [
                    'products' => [
                        self::MUG_SW_PRODUCT_CODE,
                    ],
                ],
            ],
        ]));

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/ProductAssociations/' . $this->getName() . '.yaml';
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
    public function it_creates_new_product_association_between_already_existent_products(): void
    {
        $this->importer->import(self::MUG_DW_PRODUCT_CODE);

        $product = $this->productRepository->findOneBy(['code' => self::MUG_DW_PRODUCT_CODE]);
        $this->assertInstanceOf(ProductInterface::class, $product);

        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);
        $association = $associations->first();
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals(self::UPSELL_ASSOCIATION_CODE, $association->getType()->getCode());
        $this->assertCount(1, $association->getAssociatedProducts());
        $this->assertEquals(self::MUG_SW_PRODUCT_CODE, $association->getAssociatedProducts()->first()->getCode());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_the_product_that_has_being_importer_does_not_exists_on_akeneo(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot find product "NOT-EXISTS-ON-AKENEO" on Akeneo');

        $this->importer->import('NOT-EXISTS-ON-AKENEO');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_the_product_that_has_being_imported_does_not_exists_on_the_store(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot find product "' . self::MUG_DW_PRODUCT_CODE . '" on Sylius.');

        $this->importer->import(self::MUG_DW_PRODUCT_CODE);
    }

    /**
     * @test
     */
    public function it_does_not_fail_when_the_product_association_type_does_not_exists(): void
    {
        InMemoryProductApi::addResource(Product::create(self::MUG_SW_PRODUCT_CODE, [
            'family' => 'mugs',
            'parent' => null,
            'values' => [],
            'associations' => [
                self::UPSELL_ASSOCIATION_CODE => [
                    'products' => [
                        'MUG_BAD',
                    ],
                ],
            ],
        ]));
        $this->importer->import(self::MUG_SW_PRODUCT_CODE);

        $product = $this->productRepository->findOneBy(['code' => self::MUG_SW_PRODUCT_CODE]);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEmpty($product->getAssociations());
    }

    /**
     * @test
     */
    public function it_does_not_fail_when_a_product_to_associate_does_not_exists_on_the_store(): void
    {
        $this->importer->import(self::MUG_DW_PRODUCT_CODE);

        $product = $this->productRepository->findOneBy(['code' => self::MUG_DW_PRODUCT_CODE]);
        $this->assertInstanceOf(ProductInterface::class, $product);

        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);

        $association = $associations->first();
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals(self::UPSELL_ASSOCIATION_CODE, $association->getType()->getCode());
        $this->assertEmpty($association->getAssociatedProducts());
    }

    /**
     * @test
     */
    public function it_updates_product_association_when_it_already_exists(): void
    {
        $this->importer->import(self::MUG_DW_PRODUCT_CODE);

        $product = $this->productRepository->findOneBy(['code' => 'MUG_DW']);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);

        $association = $associations->first();
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals(self::UPSELL_ASSOCIATION_CODE, $association->getType()->getCode());

        $associatedProducts = $association->getAssociatedProducts();
        $this->assertCount(1, $associatedProducts);
        $productMugSw = $this->productRepository->findOneBy(['code' => self::MUG_SW_PRODUCT_CODE]);
        $this->assertTrue($associatedProducts->contains($productMugSw));
    }

    /**
     * @test
     */
    public function it_uses_parent_product_when_the_product_that_is_being_imported_is_a_variant(): void
    {
        InMemoryProductApi::addResource(Product::create('tshirt-new-m', [
            'family' => 'T-shirt',
            'parent' => 'tshirt-new',
            'values' => [],
            'associations' => [
                self::UPSELL_ASSOCIATION_CODE => [
                    'products' => [
                        self::MUG_SW_PRODUCT_CODE,
                    ],
                ],
            ],
        ]));

        $this->importer->import('tshirt-new-m');

        $product = $this->productRepository->findOneBy(['code' => 'tshirt-new']);
        $this->assertInstanceOf(ProductInterface::class, $product);

        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);
        $association = $associations->first();
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals(self::UPSELL_ASSOCIATION_CODE, $association->getType()->getCode());
        $this->assertCount(1, $association->getAssociatedProducts());
        $this->assertEquals(self::MUG_SW_PRODUCT_CODE, $association->getAssociatedProducts()->first()->getCode());
    }
}
