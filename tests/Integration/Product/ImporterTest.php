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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
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

    /** @var ChannelRepository */
    private $channelRepository;

    /** @var Filesystem */
    private $filesystem;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.product.importer');
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->productVariantRepository = self::$container->get('sylius.repository.product_variant');
        $this->channelRepository = self::$container->get('sylius.repository.channel');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->filesystem = self::$container->get('filesystem');
        $this->fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
    }

    /**
     * @test
     */
    public function it_creates_new_product_variant_and_its_product_when_importing_variant_of_configurable_product()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $allVariants[0]);
        $this->assertInstanceOf(ProductInterface::class, $allVariants[0]->getProduct());
        $this->assertEquals('model-braided-hat', $allVariants[0]->getProduct()->getCode());
    }

    /**
     * @test
     */
    public function it_creates_proper_product_option_value_with_translations_if_missing()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(1, $variant->getOptionValues());
        $this->assertEquals('size_m', $variant->getOptionValues()[0]->getCode());
        $this->assertCount(2, $variant->getOptionValues()[0]->getTranslations());
        $this->assertEquals('it_IT', $variant->getOptionValues()[0]->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variant->getOptionValues()[0]->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_updates_alredy_existent_product_option_value()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(2, $variant->getOptionValues()[0]->getTranslations());
        $this->assertEquals('it_IT', $variant->getOptionValues()[0]->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variant->getOptionValues()[0]->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_missing_translations_while_updating_alredy_existent_product_option_value()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m/without-it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(2, $variant->getOptionValues()[0]->getTranslations());
        $this->assertEquals('it_IT', $variant->getOptionValues()[0]->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variant->getOptionValues()[0]->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_missing_option_value_while_updating_alredy_existent_product_variant()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m/without-option-values.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
        $this->assertCount(1, $variant->getOptionValues());
        $this->assertCount(2, $variant->getOptionValues()[0]->getTranslations());
        $this->assertEquals('it_IT', $variant->getOptionValues()[0]->getTranslation('it_IT')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('it_IT')->getValue());
        $this->assertEquals('en_US', $variant->getOptionValues()[0]->getTranslation('en_US')->getLocale());
        $this->assertEquals('M', $variant->getOptionValues()[0]->getTranslation('en_US')->getValue());
    }

    /**
     * @test
     */
    public function it_creates_product_and_product_variant_when_importing_simple_product()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('10627329');

        $variants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $variants);
        $this->assertInstanceOf(ProductVariantInterface::class, $variants[0]);
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertInstanceOf(ProductInterface::class, $products[0]);
    }

    /**
     * @test
     */
    public function it_updates_already_existent_parent_product_when_importing_simple_product()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/10627329.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('10627329');

        $products = $this->productRepository->findAll();
        /** @var ProductInterface $parentProduct */
        $parentProduct = $products[0];
        $this->assertEquals('NEC EX201W', $parentProduct->getName());
    }

    /**
     * @test
     */
    public function it_sets_channel_price_value_on_product_variant_according_to_channel_currency()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/EUR.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/USD.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/italy.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/usa.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/europe.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m/with-M-size-values.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        /** @var ChannelInterface $italyChannel */
        $italyChannel = $this->channelRepository->findOneByCode('italy');
        /** @var ChannelInterface $usaChannel */
        $usaChannel = $this->channelRepository->findOneByCode('usa');
        /** @var ChannelInterface $europeChannel */
        $europeChannel = $this->channelRepository->findOneByCode('europe');

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
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
    public function it_updates_channel_price_value_on_product_variant_according_channel_currency()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/EUR.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/USD.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/italy.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/usa.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/europe.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m/with-M-size-values.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m/with-channel-pricings.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        /** @var ChannelInterface $italyChannel */
        $italyChannel = $this->channelRepository->findOneByCode('italy');
        /** @var ChannelInterface $usaChannel */
        $usaChannel = $this->channelRepository->findOneByCode('usa');
        /** @var ChannelInterface $europeChannel */
        $europeChannel = $this->channelRepository->findOneByCode('europe');

        $this->importer->import('braided-hat-m');

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->findAll()[0];
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
    public function it_sets_all_channels_to_imported_products()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/EUR.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/USD.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/italy.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/usa.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/europe.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findAll()[0];
        $this->assertCount(3, $product->getChannels());
    }

    /**
     * @test
     */
    public function it_imports_all_product_images_when_importing_variants_of_configurable_product()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');
        $this->importer->import('braided-hat-l');

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(2, $allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $allVariants[0]);
        $product = $allVariants[0]->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('model-braided-hat', $product->getCode());
        $this->assertCount(2, $product->getImages());
        /** @var ProductImageInterface $image */
        foreach ($product->getImages() as $image) {
            $this->assertTrue($image->hasProductVariant($allVariants[0]) || $image->hasProductVariant($allVariants[1]));
            $this->assertEquals('image', $image->getType());
        }
    }

    /**
     * @test
     */
    public function it_updates_already_existent_product_image_without_duplicating_it()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/10627329.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('10627329');

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $allVariants[0]);
        $product = $allVariants[0]->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('10627329', $product->getCode());
        $this->assertCount(1, $product->getImages());
        /** @var ProductImageInterface $image */
        $image = $product->getImages()[0];
        $this->assertTrue($image->hasProductVariant($allVariants[0]));
        $this->assertEquals('picture', $image->getType());
        $this->assertNotEquals('path/to/existent-image/10627329.jpg', $image->getPath());
    }

    /**
     * @test
     */
    public function it_imports_new_product_image_without_associate_it_with_the_variant_if_product_is_simple()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/127469.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('127469');

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $allVariants[0]);
        $product = $allVariants[0]->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('127469', $product->getCode());
        $this->assertCount(1, $product->getImages());
        $this->assertCount(0, $allVariants[0]->getImages());
        /** @var ProductImageInterface $image */
        $image = $product->getImages()[0];
        $this->assertFalse($image->hasProductVariant($allVariants[0]));
    }

    /**
     * @test
     */
    public function it_imports_updated_product_image_without_associate_it_with_the_variant_if_product_is_simple()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/127469-with-image.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('127469');

        /** @var ProductVariantInterface[] $allVariants */
        $allVariants = $this->productVariantRepository->findAll();
        $this->assertCount(1, $allVariants);
        $this->assertInstanceOf(ProductVariantInterface::class, $allVariants[0]);
        $product = $allVariants[0]->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('127469', $product->getCode());
        $this->assertCount(1, $product->getImages());
        $this->assertCount(0, $allVariants[0]->getImages());
        /** @var ProductImageInterface $image */
        $image = $product->getImages()[0];
        $this->assertFalse($image->hasProductVariant($allVariants[0]));
        $this->assertEquals('picture', $image->getType());
        $this->assertNotEquals('path/to/existent-image/127469.jpg', $image->getPath());
    }

    /**
     * @test
     */
    public function it_imports_product_as_disabled_if_it_is_disabled_on_akeneo_and_has_not_a_parent_model()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('16466450');

        $product = $this->productRepository->findOneByCode('16466450');
        $this->assertFalse($product->isEnabled());
        $this->assertFalse($product->getVariants()->first()->isEnabled());
    }

    /**
     * @test
     */
    public function it_imports_product_as_enabled_even_if_is_disabled_on_akeneo_but_has_a_parent_model()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-s');

        $product = $this->productRepository->findOneByCode('model-braided-hat');
        $this->assertTrue($product->isEnabled());
    }

    /**
     * @test
     */
    public function it_imports_variant_of_a_configurable_product_as_disabled_if_it_is_disabled_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-s');

        $productVariant = $this->productVariantRepository->findOneByCode('braided-hat-s');
        $this->assertFalse($productVariant->isEnabled());
    }

    /**
     * @test
     */
    public function it_updates_existing_product_attribute_value()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneByCode('model-braided-hat');
        $this->assertEquals('cotton', $product->getAttributeByCodeAndLocale('material', 'en_US')->getValue());
        $this->assertEquals('cotone', $product->getAttributeByCodeAndLocale('material', 'it_IT')->getValue());
    }

    /**
     * @test
     */
    public function it_downloads_file_from_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');
        $this->assertTrue(
            $this->filesystem->exists(
                self::$container->getParameter(
                    'sylius_core.public_dir'
                ) . '/media/attachment/product/1/3/9/e/139e9b32956237c28b5d9a36d00a34254075316f_media_11556.jpeg'
            )
        );
    }

    /**
     * @test
     */
    public function it_sets_product_taxa_from_akeneo_discarding_those_set_on_sylius()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/10627329-with-taxa.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('10627329');

        /** @var ProductInterface[] $allProducts */
        $allProducts = $this->productRepository->findAll();
        $this->assertCount(1, $allProducts);
        $product = $allProducts[0];
        $this->assertInstanceOf(ProductInterface::class, $product);
        $this->assertEquals('10627329', $product->getCode());
        $this->assertCount(3, $product->getTaxons());
    }

    /**
     * @test
     */
    public function it_removes_product_images_removed_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat-with-variation-image.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_l.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-l.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        $product = $this->productRepository->findOneByCode('model-braided-hat');
        $this->assertCount(1, $product->getImages());
    }

    /**
     * @test
     */
    public function it_does_not_remove_product_images_of_other_variants_removed_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat-with-variation-image.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOptionValue/size_l.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-l.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-l');

        $product = $this->productRepository->findOneByCode('model-braided-hat');
        $this->assertCount(2, $product->getImages());
    }

    /**
     * @test
     */
    public function it_does_not_fail_with_empty_translations()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Currency/USD.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Channel/usa.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        // Product 127469 in fixture does not have a value for the "description" attribute.
        $this->importer->import('127469');

        $product = $this->productRepository->findOneByCode('127469');
        $this->assertNotNull($product);
    }

    /**
     * @test
     */
    public function it_removes_existing_product_attributes_values_if_they_are_empty_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/model-braided-hat.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('braided-hat-m');

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneByCode('model-braided-hat');
        $this->assertFalse($product->hasAttributeByCodeAndLocale('supplier', 'it_IT'));
        $this->assertFalse($product->hasAttributeByCodeAndLocale('supplier', 'en_US'));
    }

    /**
     * @test
     */
    public function it_imports_product_without_family()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('no-family-product');

        $product = $this->productRepository->findOneByCode('no-family-product');
        $this->assertNotNull($product);
    }

    /** @test */
    public function it_disable_old_product_while_importing_product_variant_from_configurable_that_was_simple(): void
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOption/eu_shoes_size.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/1111111188.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/1111111188.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('1111111188');

        $oldProduct = $this->productRepository->findOneByCode('1111111188');
        $newProduct = $this->productRepository->findOneByCode('climbingshoe');
        $productVariant = $this->productVariantRepository->findOneBy(['code' => '1111111188']);
        self::assertInstanceOf(ProductInterface::class, $oldProduct);
        self::assertInstanceOf(ProductInterface::class, $newProduct);
        self::assertInstanceOf(ProductVariantInterface::class, $productVariant);

        self::assertFalse($oldProduct->isEnabled());
        self::assertTrue($newProduct->isEnabled());
        self::assertTrue($productVariant->isEnabled());
    }

    /** @test */
    public function it_enables_product_without_variants_while_importing_a_new_one(): void
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/en_US.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Locale/it_IT.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ProductOption/eu_shoes_size.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/Product/climbingshoe.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->importer->import('1111111186');

        $product = $this->productRepository->findOneByCode('climbingshoe');
        $productVariant = $this->productVariantRepository->findOneBy(['code' => '1111111186']);
        self::assertInstanceOf(ProductInterface::class, $product);
        self::assertInstanceOf(ProductVariantInterface::class, $productVariant);

        self::assertTrue($product->isEnabled());
        self::assertTrue($productVariant->isEnabled());
    }
}
