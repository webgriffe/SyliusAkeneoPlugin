<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\ProductModel;

use DateTime;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Webgriffe\SyliusAkeneoPlugin\DataFixtures\DataFixture;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeOptionApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryFamilyApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryFamilyVariantApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductModelApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeOption;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeType;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Family;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\FamilyVariant;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ProductModel;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\Importer;

final class ImporterTest extends KernelTestCase
{
    private const STAR_WARS_TSHIRT_MODEL_CODE = 'STAR_WARS_TSHIRT';

    private const STAR_WARS_TSHIRT_M_PRODUCT_CODE = 'STAR_WARS_TSHIRT_M';

    private Importer $importer;

    private ProductRepositoryInterface $productRepository;

    private ProductVariantRepositoryInterface $productVariantRepository;

    private ChannelRepositoryInterface $channelRepository;

    private PurgerLoader $fixtureLoader;

    private Filesystem $filesystem;

    private Family $tShirtFamily;

    private AttributeOption $sizeLAttributeOption;

    private ProductModel $starWarsTShirtProductModel;

    private Product $startWarsTShirtMAkeneoProduct;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.product_model.importer');
        $this->productRepository = self::getContainer()->get('sylius.repository.product');
        $this->productVariantRepository = self::getContainer()->get('sylius.repository.product_variant');
        $this->channelRepository = self::getContainer()->get('sylius.repository.channel');
        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->filesystem = self::getContainer()->get('filesystem');

        $this->tShirtFamily = Family::create('t-shirt', [
            'attributes' => ['variation_image', 'supplier'],
        ]);
        InMemoryFamilyApi::addResource($this->tShirtFamily);

        $attachmentAttribute = Attribute::create('attachment', [
            'type' => AttributeType::FILE,
        ]);
        InMemoryAttributeApi::addResource($attachmentAttribute);
        $skuAttribute = Attribute::create('sku', [
            'type' => AttributeType::TEXT,
            'labels' => ['en_US' => 'SKU'],
        ]);
        InMemoryAttributeApi::addResource($skuAttribute);
        $this->sizeAttribute = Attribute::create('size', [
            'type' => AttributeType::SIMPLE_SELECT,
            'labels' => ['en_US' => 'Size'],
        ]);
        InMemoryAttributeApi::addResource($this->sizeAttribute);

        $sizeMAttributeOption = AttributeOption::create($this->sizeAttribute->code, 'm', 0, [
            'en_US' => 'M', 'it_IT' => 'M',
        ]);
        InMemoryAttributeOptionApi::addResource($sizeMAttributeOption);

        $this->sizeLAttributeOption = AttributeOption::create($this->sizeAttribute->code, 'l', 0, [
            'en_US' => 'L', 'it_IT' => 'L',
        ]);
        InMemoryAttributeOptionApi::addResource($this->sizeLAttributeOption);

        $tShirtBySizeFamilyVariant = FamilyVariant::create('t-shirt_by_size', [
            'variant_attribute_sets' => [
                [
                    'level' => 1,
                    'axes' => [$this->sizeAttribute->code],
                    'attributes' => [$skuAttribute->code, $this->sizeAttribute->code],
                ],
            ],
        ]);
        InMemoryFamilyVariantApi::addResource($this->tShirtFamily->code, $tShirtBySizeFamilyVariant);

        $this->starWarsTShirtProductModel = ProductModel::create(self::STAR_WARS_TSHIRT_MODEL_CODE, [
            'family' => $this->tShirtFamily->code,
            'family_variant' => $tShirtBySizeFamilyVariant->code,
        ]);
        InMemoryProductModelApi::addResource($this->starWarsTShirtProductModel);

        $this->startWarsTShirtMAkeneoProduct = Product::create(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, [
            'family' => $this->tShirtFamily->code,
            'parent' => $this->starWarsTShirtProductModel->code,
            'values' => [
                $this->sizeAttribute->code => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => $sizeMAttributeOption->code,
                    ],
                ],
            ],
        ]);
        InMemoryProductApi::addResource($this->startWarsTShirtMAkeneoProduct);

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/ProductModel/' . $this->getName() . '.yaml';
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

        $this->filesystem->remove(
            self::getContainer()->getParameter('sylius_core.public_dir') . '/media/',
        );
    }

    /**
     * @test
     */
    public function it_returns_all_product_model_identifiers(): void
    {
        $identifiers = $this->importer->getIdentifiersModifiedSince(new DateTime('1990-01-01 00:00:00'));

        $this->assertEquals([self::STAR_WARS_TSHIRT_MODEL_CODE], $identifiers);
    }

    /**
     * @test
     */
    public function it_creates_new_product_and_its_product_variants_when_importing_product_model(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_MODEL_CODE);

        $allProducts = $this->productRepository->findAll();
        $this->assertCount(1, $allProducts);
        $product = $allProducts[0];
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $product->getCode());
        $this->assertCount(1, $product->getVariants());

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = $allVariants[0];
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $variant->getCode());
    }

    /**
     * @test
     */
    public function it_updates_product__and_its_product_variants_when_importing_product_model(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_MODEL_CODE);

        $allProducts = $this->productRepository->findAll();
        $this->assertCount(1, $allProducts);
        $product = $allProducts[0];
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $product->getCode());
        $this->assertCount(1, $product->getVariants());

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = $allVariants[0];
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $variant->getCode());
    }
}
