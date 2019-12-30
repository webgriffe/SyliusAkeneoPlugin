<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\ProductModel;

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

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var PurgerLoader */
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
    public function it_updates_already_existent_product()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/en_US_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/it_IT_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/product.yaml'
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        $this->importer->import('MUG_SW');
        /** @var ProductInterface[] $products */
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('New Star Wars mug name', $products[0]->getTranslation('en_US')->getName());
        $this->assertEquals('Nuovo nome tazza Star Wars', $products[0]->getTranslation('it_IT')->getName());
        $this->assertEquals('new-star-wars-mug', $products[0]->getSlug());
        $this->assertCount(1, $products[0]->getImages());
        $this->assertEquals('main', $products[0]->getImages()[0]->getType());
    }

    /**
     * @test
     */
    public function it_creates_new_product_if_it_does_not_exists()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/en_US_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/it_IT_locale.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        $this->importer->import('MUG_SW');
        /** @var ProductInterface[] $products */
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('New Star Wars mug name', $products[0]->getTranslation('en_US')->getName());
        $this->assertEquals('Nuovo nome tazza Star Wars', $products[0]->getTranslation('it_IT')->getName());
        $this->assertEquals('new-star-wars-mug', $products[0]->getSlug());
        $this->assertCount(1, $products[0]->getImages());
        $this->assertEquals('main', $products[0]->getImages()[0]->getType());
    }

    /**
     * @test
     */
    public function it_imports_product_model_with_proper_family_variant()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/en_US_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/it_IT_locale.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/size_product_option.yaml'
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        $this->importer->import('model-braided-hat');
        /** @var ProductInterface[] $products */
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('Braided hat ', $products[0]->getName());
        $this->assertCount(1, $products[0]->getOptions());
        $this->assertEquals('size', $products[0]->getOptions()[0]->getCode());
    }
}
