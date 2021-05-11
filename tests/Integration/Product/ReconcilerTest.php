<?php


namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Product;


use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Bundle\ChannelBundle\Doctrine\ORM\ChannelRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;

final class ReconcilerTest extends KernelTestCase
{
    /** @var ReconcilerInterface */
    private $reconciler;

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
        $this->reconciler = self::$container->get('webgriffe_sylius_akeneo.product.importer');
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
    public function it_disable_product_variants_and_product_on_sylius_when_there_are_no_more_product_variants_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Product/product-with-two-variants.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->reconciler->reconcile([]);

        /** @var ?ProductVariantInterface $productVariant1 */
        $productVariant1 = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        /** @var ?ProductVariantInterface $productVariant2 */
        $productVariant2 = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertNotNull($productVariant1);
        $this->assertNotNull($productVariant2);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariant1);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariant2);
        $this->assertInstanceOf(ProductInterface::class, $productVariant1->getProduct());
        $this->assertEquals(false, $productVariant1->isEnabled());
        $this->assertEquals(false, $productVariant2->isEnabled());
        $this->assertEquals(false, $productVariant1->getProduct()->isEnabled());
    }

    /**
     * @test
     */
    public function it_disable_the_product_variant_on_sylius_when_it_no_longer_exists_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Product/product-with-two-variants.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->reconciler->reconcile(['23423545']);

        /** @var ?ProductVariantInterface $productVariantEnabled */
        $productVariantEnabled = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        /** @var ?ProductVariantInterface $productVariantDisabled */
        $productVariantDisabled = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertNotNull($productVariantEnabled);
        $this->assertNotNull($productVariantDisabled);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantEnabled);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantDisabled);
        $this->assertInstanceOf(ProductInterface::class, $productVariantEnabled->getProduct());
        $this->assertEquals(true, $productVariantEnabled->isEnabled());
        $this->assertEquals(false, $productVariantDisabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->getProduct()->isEnabled());
    }

    /**
     * @test
     */
    public function it_does_not_disable_the_product_variants_on_sylius_when_they_exist_on_akeneo()
    {
        $this->fixtureLoader->load(
            [
                __DIR__ . '/../DataFixtures/ORM/resources/Product/product-with-two-variants.yaml',
            ],
            [],
            [],
            PurgeMode::createDeleteMode()
        );

        $this->reconciler->reconcile(['23423545', '567567']);

        /** @var ?ProductVariantInterface $productVariantEnabled */
        $productVariantEnabled = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        /** @var ?ProductVariantInterface $productVariantDisabled */
        $productVariantDisabled = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertNotNull($productVariantEnabled);
        $this->assertNotNull($productVariantDisabled);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantEnabled);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantDisabled);
        $this->assertInstanceOf(ProductInterface::class, $productVariantEnabled->getProduct());
        $this->assertEquals(true, $productVariantEnabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->getProduct()->isEnabled());
    }
}
