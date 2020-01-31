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
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_SW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/MUG_DW.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductAssociationType/UPSELL.yaml',
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
}
