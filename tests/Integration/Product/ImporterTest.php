<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Product;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Bundle\ChannelBundle\Doctrine\ORM\ChannelRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
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
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class ImporterTest extends KernelTestCase
{
    private const STAR_WARS_TSHIRT_MODEL_CODE = 'STAR_WARS_TSHIRT';

    private const STAR_WARS_TSHIRT_M_PRODUCT_CODE = 'STAR_WARS_TSHIRT_M';

    private ImporterInterface $importer;

    private ProductRepositoryInterface $productRepository;

    private ProductVariantRepository $productVariantRepository;

    private PurgerLoader $fixtureLoader;

    private ChannelRepository $channelRepository;

    private Filesystem $filesystem;

    private Product $startWarsTShirtMAkeneoProduct;

    private Attribute $sizeAttribute;

    private ProductModel $starWarsTShirtProductModel;

    private Family $tShirtFamily;

    private AttributeOption $sizeLAttributeOption;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::getContainer()->get('webgriffe_sylius_akeneo.product.importer');
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

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Importer/Product/' . $this->getName() . '.yaml';
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
    public function it_creates_new_product_variant_and_its_product_when_importing_variant_of_configurable_product(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = $allVariants[0];
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertInstanceOf(ProductInterface::class, $variant->getProduct());
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $variant->getCode());
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $variant->getProduct()->getCode());
    }

    /**
     * @test
     */
    public function it_creates_proper_product_option_value_with_translations(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(1, $variant->getOptionValues());
        $productVariantOptionValue = $variant->getOptionValues()->first();
        $this->assertEquals('size_m', $productVariantOptionValue->getCode());
        $this->assertCount(2, $productVariantOptionValue->getTranslations());
        $this->assertEquals('it_IT', $productVariantOptionValue->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $productVariantOptionValue->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $productVariantOptionValue->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $productVariantOptionValue->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_updates_already_existent_product_option_value(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $variantOptionValues = $variant->getOptionValues();
        $this->assertCount(1, $variantOptionValues);
        $variantOptionValue = $variantOptionValues->first();
        $this->assertCount(2, $variantOptionValue->getTranslations());
        $this->assertEquals('it_IT', $variantOptionValue->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variantOptionValue->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variantOptionValue->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variantOptionValue->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_missing_translations_while_updating_already_existent_product_option_value(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $variantOptionValues = $variant->getOptionValues();
        $this->assertCount(1, $variantOptionValues);
        $variantOptionValue = $variantOptionValues->first();
        $this->assertCount(2, $variantOptionValue->getTranslations());
        $this->assertEquals('it_IT', $variantOptionValue->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variantOptionValue->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variantOptionValue->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variantOptionValue->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_missing_option_value_while_updating_already_existent_product_variant(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(1, $variant->getOptionValues());
        $productVariantOptionValue = $variant->getOptionValues()->first();
        $this->assertCount(2, $productVariantOptionValue->getTranslations());
        $this->assertEquals('it_IT', $productVariantOptionValue->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $productVariantOptionValue->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $productVariantOptionValue->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $productVariantOptionValue->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_product_and_product_variant_when_importing_simple_product(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $variants);
        $variant = reset($variants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $variant->getCode());
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $product = reset($products);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $product->getCode());
    }

    /**
     * @test
     */
    public function it_updates_already_existent_parent_product_when_importing_simple_product(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;
        $this->startWarsTShirtMAkeneoProduct->values = [
            'name' => [[
                'locale' => null,
                'scope' => null,
                'data' => 'Star Wars T-Shirt M',
            ]],
        ];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $parentProduct = reset($products);
        $this->assertInstanceOf(ProductInterface::class, $parentProduct);
        $this->assertEquals('Star Wars T-Shirt M', $parentProduct->getName());
    }

    /**
     * @test
     */
    public function it_sets_channel_price_value_on_product_variant_according_to_channel_currency(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values = [
            'price' => [[
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => 30.99,
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => 33.99,
                        'currency' => 'USD',
                    ],
                ],
            ]],
        ];

        /** @var ChannelInterface $italyChannel */
        $italyChannel = $this->channelRepository->findOneByCode('italy');
        /** @var ChannelInterface $usaChannel */
        $usaChannel = $this->channelRepository->findOneByCode('usa');
        /** @var ChannelInterface $europeChannel */
        $europeChannel = $this->channelRepository->findOneByCode('europe');

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $variants);
        $variant = reset($variants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $channelPricing = $variant->getChannelPricingForChannel($italyChannel);
        $this->assertNotNull($channelPricing);
        $this->assertEquals(3099, $channelPricing->getPrice());
        $channelPricing = $variant->getChannelPricingForChannel($usaChannel);
        $this->assertNotNull($channelPricing);
        $this->assertEquals(3399, $channelPricing->getPrice());
        $channelPricing = $variant->getChannelPricingForChannel($europeChannel);
        $this->assertNotNull($channelPricing);
        $this->assertEquals(3099, $channelPricing->getPrice());
    }

    /**
     * @test
     */
    public function it_updates_channel_price_value_on_product_variant_according_channel_currency(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values = [
            'price' => [[
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => 30.99,
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => 33.99,
                        'currency' => 'USD',
                    ],
                ],
            ]],
        ];

        /** @var ChannelInterface $italyChannel */
        $italyChannel = $this->channelRepository->findOneByCode('italy');
        /** @var ChannelInterface $usaChannel */
        $usaChannel = $this->channelRepository->findOneByCode('usa');
        /** @var ChannelInterface $europeChannel */
        $europeChannel = $this->channelRepository->findOneByCode('europe');

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $variants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $variants);
        $variant = reset($variants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $channelPricings = $variant->getChannelPricingForChannel($italyChannel);
        $this->assertNotNull($channelPricings);
        $this->assertEquals(3099, $channelPricings->getPrice());
        $channelPricing = $variant->getChannelPricingForChannel($usaChannel);
        $this->assertNotNull($channelPricing);
        $this->assertEquals(3399, $channelPricing->getPrice());
        $channelPricing = $variant->getChannelPricingForChannel($europeChannel);
        $this->assertNotNull($channelPricing);
        $this->assertEquals(3099, $channelPricing->getPrice());
    }

    /**
     * @test
     */
    public function it_sets_all_channels_to_imported_products(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values['price'] = [[
            'locale' => null,
            'scope' => null,
            'data' => [
                [
                    'amount' => 30.99,
                    'currency' => 'EUR',
                ],
                [
                    'amount' => 33.99,
                    'currency' => 'USD',
                ],
            ],
        ]];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $product = reset($products);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertCount(3, $product->getChannels());
    }

    /**
     * @test
     */
    public function it_imports_all_product_images_when_importing_variants_of_configurable_product(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values = [
            'image' => [[
                'locale' => null,
                'scope' => null,
                'data' => 'star_wars_m.jpeg',
            ]],
        ];
        $startWarsTShirtLAkeneoProduct = Product::create('STAR_WARS_TSHIRT_L', [
            'family' => $this->tShirtFamily->code,
            'parent' => $this->starWarsTShirtProductModel->code,
            'values' => [
                $this->sizeAttribute->code => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => $this->sizeLAttributeOption->code,
                    ],
                ],
                'image' => [[
                    'locale' => null,
                    'scope' => null,
                    'data' => 'star_wars_l.jpeg',
                ]],
            ],
        ]);
        InMemoryProductApi::addResource($startWarsTShirtLAkeneoProduct);

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);
        $this->importer->import('STAR_WARS_TSHIRT_L');

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(2, $allVariants);
        $mVariant = $allVariants[0];
        $lVariant = $allVariants[1];
        $this->assertInstanceOf(ProductVariantInterface::class, $mVariant);
        $this->assertInstanceOf(ProductVariantInterface::class, $lVariant);
        $product = $mVariant->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $product->getCode());
        $this->assertCount(2, $product->getImages());
        /** @var ProductImageInterface $image */
        foreach ($product->getImages() as $image) {
            $this->assertTrue($image->hasProductVariant($mVariant) || $image->hasProductVariant($lVariant));
            $this->assertEquals('image', $image->getType());
        }
    }

    /**
     * @test
     */
    public function it_updates_already_existent_product_image_without_duplicating_it(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;
        $this->startWarsTShirtMAkeneoProduct->values['picture'] = [[
            'locale' => null,
            'scope' => null,
            'data' => 'star_wars_m.jpeg',
        ]];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = reset($allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $product = $variant->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $product->getCode());

        $productImages = $product->getImages();
        $this->assertCount(1, $productImages);
        $image = $productImages->first();
        $this->assertInstanceOf(ProductImageInterface::class, $image);
        $this->assertTrue($image->hasProductVariant($variant));
        $this->assertEquals('picture', $image->getType());
        $this->assertNotEquals('path/to/existent-image.jpg', $image->getPath());
    }

    /**
     * @test
     */
    public function it_imports_new_product_image_without_associate_it_with_the_variant_if_product_is_simple(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;
        $this->startWarsTShirtMAkeneoProduct->values['picture'] = [[
            'locale' => null,
            'scope' => null,
            'data' => 'star_wars_m.jpeg',
        ]];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = reset($allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $product = $variant->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $product->getCode());
        $productImages = $product->getImages();
        $this->assertCount(1, $productImages);
        $this->assertCount(0, $variant->getImages());

        $productImage = $productImages->first();
        $this->assertInstanceOf(ProductImageInterface::class, $productImage);
        $this->assertFalse($productImage->hasProductVariant($variant));
    }

    /**
     * @test
     */
    public function it_imports_updated_product_image_without_associate_it_with_the_variant_if_product_is_simple(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;
        $this->startWarsTShirtMAkeneoProduct->values['picture'] = [[
            'locale' => null,
            'scope' => null,
            'data' => 'star_wars_m.jpeg',
        ]];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);

        $variant = reset($allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $product = $variant->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);

        $this->assertEquals(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE, $product->getCode());
        $productImages = $product->getImages();
        $this->assertCount(1, $productImages);
        $this->assertCount(0, $variant->getImages());

        $image = $productImages->first();
        $this->assertInstanceOf(ProductImageInterface::class, $image);
        $this->assertFalse($image->hasProductVariant($variant));
        $this->assertEquals('picture', $image->getType());
        $this->assertNotEquals('path/to/existent-image.jpg', $image->getPath());
    }

    /**
     * @test
     */
    public function it_removes_product_images_removed_on_akeneo(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertCount(0, $product->getImages());

        $variant = $product->getVariants()->first();
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertCount(0, $variant->getImages());
    }

    /**
     * @test
     */
    public function it_does_not_remove_product_images_of_other_variants(): void
    {
        $startWarsTShirtLAkeneoProduct = Product::create('STAR_WARS_TSHIRT_L', [
            'family' => $this->tShirtFamily->code,
            'parent' => $this->starWarsTShirtProductModel->code,
            'values' => [
                $this->sizeAttribute->code => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => $this->sizeLAttributeOption->code,
                    ],
                ],
                'image' => [[
                    'locale' => null,
                    'scope' => null,
                    'data' => 'star_wars_l.jpeg',
                ]],
            ],
        ]);
        InMemoryProductApi::addResource($startWarsTShirtLAkeneoProduct);

        $this->importer->import('STAR_WARS_TSHIRT_L');

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertCount(2, $product->getImages());
    }

    /**
     * @test
     */
    public function it_imports_product_as_disabled_if_it_is_disabled_on_akeneo_and_has_not_a_parent_model(): void
    {
        $this->startWarsTShirtMAkeneoProduct->parent = null;
        $this->startWarsTShirtMAkeneoProduct->enabled = false;

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);
        $this->assertFalse($product->isEnabled());
        $this->assertFalse($product->getVariants()->first()->isEnabled());
    }

    /**
     * @test
     */
    public function it_enables_product_if_product_model_is_enabled_but_disable_variant_if_akeneo_product_is_disabled(): void
    {
        $this->startWarsTShirtMAkeneoProduct->enabled = false;

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertTrue($product->isEnabled());

        $productVariant = $this->productVariantRepository->findOneByCode(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);
        $this->assertFalse($productVariant->isEnabled());
    }

    /**
     * @test
     */
    public function it_updates_existing_product_attribute_value(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values['material'] = [
            [
                'locale' => 'it_IT',
                'scope' => null,
                'data' => 'cotone',
            ],
            [
                'locale' => 'en_US',
                'scope' => null,
                'data' => 'cotton',
            ],
        ];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('cotton', $product->getAttributeByCodeAndLocale('material', 'en_US')->getValue());
        $this->assertEquals('cotone', $product->getAttributeByCodeAndLocale('material', 'it_IT')->getValue());
    }

    /**
     * @test
     */
    public function it_downloads_file_from_akeneo(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values['attachment'] = [[
            'locale' => null,
            'scope' => null,
            'data' => 'sample.pdf',
        ]];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $this->assertTrue(
            $this->filesystem->exists(
                self::getContainer()->getParameter(
                    'sylius_core.public_dir',
                ) . '/media/attachment/product/sample.pdf',
            ),
        );
    }

    /**
     * @test
     */
    public function it_sets_product_taxa_from_akeneo_discarding_those_set_on_sylius(): void
    {
        $this->startWarsTShirtMAkeneoProduct->categories = [
            'nec',
            'pc_monitors',
            'tvs_projectors_sales',
        ];

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $allProducts = $this->productRepository->findAll();
        $this->assertCount(1, $allProducts);
        $product = reset($allProducts);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $product->getCode());
        $this->assertCount(3, $product->getTaxons());
    }

    /**
     * @test
     */
    public function it_does_not_fail_with_empty_translations(): void
    {
        $this->startWarsTShirtMAkeneoProduct->values['price'] = [[
            'locale' => null,
            'scope' => null,
            'data' => [
                [
                    'amount' => 299.99,
                    'currency' => 'USD',
                ],
            ],
        ]];
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertNotNull($product);
    }

    /**
     * @test
     */
    public function it_removes_existing_product_attributes_values_if_they_are_empty_on_akeneo(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertFalse($product->hasAttributeByCodeAndLocale('supplier', 'it_IT'));
        $this->assertFalse($product->hasAttributeByCodeAndLocale('supplier', 'en_US'));
    }

    /**
     * @test
     */
    public function it_imports_product_without_family(): void
    {
        $this->startWarsTShirtMAkeneoProduct->family = null;

        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $this->assertNotNull($product);
    }

    /**
     * @test
     */
    public function it_disable_old_product_while_importing_product_variant_from_configurable_that_was_simple(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $oldProduct = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);
        $newProduct = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $productVariant = $this->productVariantRepository->findOneBy(['code' => self::STAR_WARS_TSHIRT_M_PRODUCT_CODE]);
        self::assertInstanceOf(ProductInterface::class, $oldProduct);
        self::assertInstanceOf(ProductInterface::class, $newProduct);
        self::assertInstanceOf(ProductVariantInterface::class, $productVariant);

        self::assertFalse($oldProduct->isEnabled());
        self::assertTrue($newProduct->isEnabled());
        self::assertTrue($productVariant->isEnabled());
    }

    /**
     * @test
     */
    public function it_enables_an_already_existing_disabled_product_without_variants_while_importing_a_new_one_variant_for_that(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        $product = $this->productRepository->findOneByCode(self::STAR_WARS_TSHIRT_MODEL_CODE);
        $productVariant = $this->productVariantRepository->findOneBy(['code' => self::STAR_WARS_TSHIRT_M_PRODUCT_CODE]);
        self::assertInstanceOf(ProductInterface::class, $product);
        self::assertInstanceOf(ProductVariantInterface::class, $productVariant);

        self::assertTrue($product->isEnabled());
        self::assertTrue($productVariant->isEnabled());
    }

    /**
     * @test
     */
    public function it_does_not_duplicate_product_option_values_when_changed(): void
    {
        $this->importer->import(self::STAR_WARS_TSHIRT_M_PRODUCT_CODE);

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $variant = reset($allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variant);
        $this->assertInstanceOf(ProductInterface::class, $variant->getProduct());
        $this->assertEquals(self::STAR_WARS_TSHIRT_MODEL_CODE, $variant->getProduct()->getCode());
        $this->assertCount(1, $variant->getOptionValues());

        $optionValue = $variant->getOptionValues()->first();
        $this->assertInstanceOf(ProductOptionValueInterface::class, $optionValue);
        $this->assertEquals($this->sizeAttribute->code, $optionValue->getOptionCode());
        $this->assertEquals('M', $optionValue->getValue());
        $this->assertEquals('size_m', $optionValue->getCode());
    }
}
