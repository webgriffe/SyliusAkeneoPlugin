<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Product;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    /** @var ImporterInterface */
    private $importer;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductVariantRepository */
    private $productVariantRepository;

    /** @var PurgerLoader */
    private $fixtureLoader;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.product.importer');
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->productVariantRepository = self::$container->get('sylius.repository.product_variant');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
    }

    /**
     * @test
     */
    public function it_imports_product_variant_of_a_product_model()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/en_US_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/product_model-braided-hat.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        $this->importer->import('braided-hat-m');

        $this->assertCount(1, $this->productVariantRepository->findAll());
    }
}
