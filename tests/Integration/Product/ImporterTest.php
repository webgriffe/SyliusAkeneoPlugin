<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Product;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Bundle\ChannelBundle\Doctrine\ORM\ChannelRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
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

    /** @var ChannelRepository */
    private $channelRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->importer = self::$container->get('webgriffe_sylius_akeneo.product.importer');
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->productVariantRepository = self::$container->get('sylius.repository.product_variant');
        $this->channelRepository = self::$container->get('sylius.repository.channel');
        $this->fixtureLoader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine');
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
        self::$kernel->getContainer()->get('doctrine')->reset(); // Hack to get rid of weird collection keys loading

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
        self::$kernel->getContainer()->get('doctrine')->reset(); // Hack to get rid of weird collection keys loading

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
        self::$kernel->getContainer()->get('doctrine')->reset(); // Hack to get rid of weird collection keys loading

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
        self::$kernel->getContainer()->get('doctrine')->reset(); // Hack to get rid of weird collection keys loading
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
                __DIR__ . '/../DataFixtures/ORM/resources/ProductVariant/braided-hat-m.yaml',
                __DIR__ . '/../DataFixtures/ORM/resources/ChannelPricing/braided-hat-m-italy.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );
        self::$kernel->getContainer()->get('doctrine')->reset(); // Hack to get rid of weird collection keys loading
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
}
