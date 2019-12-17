<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\ProductModel;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    /**
     * @var ImporterInterface
     */
    private $importer;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var PurgerLoader
     */
    private $fixtureLoader;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.product_model.importer');
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
    }

    /**
     * @test
     */
    public function it_imports_one_product()
    {
        $this->fixtureLoader->load([__DIR__ . '/../DataFixtures/ORM/resources/product.yaml'], [], [], PurgeMode::createDeleteMode());
        $this->importer->import('MUG_SW');
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('New Star Wars mug name', $products[0]->getName());
    }
}