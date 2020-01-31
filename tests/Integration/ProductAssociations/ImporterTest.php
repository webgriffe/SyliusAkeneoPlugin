<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\ProductAssociations;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    /** @var ImporterInterface */
    private $importer;

    /** @var PurgerLoader */
    private $fixtureLoader;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.product_associations.importer');
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
    }

    /**
     * @test
     */
    public function it_creates_new_product_association_between_already_existent_products()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociationType/UPSELL.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_SW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_DW.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('MUG_DW');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => 'MUG_DW']);
        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);
        $association = $associations[0];
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals('UPSELL', $association->getType()->getCode());
        $this->assertCount(1, $association->getAssociatedProducts());
        $this->assertEquals('MUG_SW', $association->getAssociatedProducts()->first()->getCode());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_the_product_that_has_being_importer_does_not_exists_on_akeneo()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find product "NOT-EXISTS-ON-AKENEO" on Akeneo');

        $this->importer->import('NOT-EXISTS-ON-AKENEO');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_the_product_that_has_being_importer_does_not_exists_on_the_store()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find product "MUG_DW" on Sylius.');

        $this->importer->import('MUG_DW');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_the_product_association_type_does_not_exists_and_it_has_products()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_SW.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'There are products for the association type "PACK" but it does not exists on Sylius.'
        );

        $this->importer->import('MUG_SW');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_a_product_to_associate_does_not_exists_on_the_store()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_DW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociationType/UPSELL.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot associate the product "MUG_SW" to product "MUG_DW" because the former does not exists on Sylius'
        );

        $this->importer->import('MUG_DW');
    }

    /**
     * @test
     */
    public function it_updates_product_association_when_it_already_exists()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociationType/UPSELL.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_SW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_DW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_ANOTHER.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociation/MUG_DW_UPSELL_MUG_ANOTHER.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('MUG_DW');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => 'MUG_DW']);
        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);
        $association = $associations[0];
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals('UPSELL', $association->getType()->getCode());

        $associatedProducts = $association->getAssociatedProducts();
        $this->assertCount(2, $associatedProducts);
        $productMugSw = $this->productRepository->findOneBy(['code' => 'MUG_SW']);
        $this->assertTrue($associatedProducts->contains($productMugSw));
        $productMugAnother = $this->productRepository->findOneBy(['code' => 'MUG_ANOTHER']);
        $this->assertTrue($associatedProducts->contains($productMugAnother));
    }

    /**
     * @test
     */
    public function it_uses_parent_product_when_the_product_that_is_being_imported_is_a_variant()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociationType/UPSELL.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOption/size.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_SW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/tshirt-new.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/tshirt-new-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('tshirt-new-m');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => 'tshirt-new']);
        $associations = $product->getAssociations();
        $this->assertCount(1, $associations);
        $association = $associations[0];
        $this->assertEquals($product->getId(), $association->getOwner()->getId());
        $this->assertEquals('UPSELL', $association->getType()->getCode());
        $this->assertCount(1, $association->getAssociatedProducts());
        $this->assertEquals('MUG_SW', $association->getAssociatedProducts()->first()->getCode());
    }
}
