<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Product;

use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Webgriffe\SyliusAkeneoPlugin\DataFixtures\DataFixture;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerInterface;

final class ReconcilerTest extends KernelTestCase
{
    private ReconcilerInterface $reconciler;

    private ProductVariantRepository $productVariantRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->reconciler = self::getContainer()->get('webgriffe_sylius_akeneo.product.importer');
        $this->productVariantRepository = self::getContainer()->get('sylius.repository.product_variant');
        $fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');

        $ORMResourceFixturePath = DataFixture::path . '/ORM/resources/Reconciler/Product/' . $this->getName() . '.yaml';
        if (file_exists($ORMResourceFixturePath)) {
            $fixtureLoader->load(
                [$ORMResourceFixturePath],
                [],
                [],
                PurgeMode::createDeleteMode(),
            );
        } else {
            $fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());
        }
    }

    /**
     * @test
     */
    public function it_disable_product_variants_and_product_on_sylius_when_there_are_no_more_product_variants_on_akeneo(): void
    {
        $this->reconciler->reconcile([]);

        $productVariant1 = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariant1);
        $productVariant2 = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariant2);
        $product = $productVariant1->getProduct();
        $this->assertInstanceOf(ProductInterface::class, $product);

        $this->assertEquals(false, $productVariant1->isEnabled());
        $this->assertEquals(false, $productVariant2->isEnabled());
        $this->assertEquals(false, $product->isEnabled());
    }

    /**
     * @test
     */
    public function it_disable_the_product_variant_on_sylius_when_it_no_longer_exists_on_akeneo(): void
    {
        $this->reconciler->reconcile(['23423545']);

        $productVariantEnabled = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantEnabled);
        $productVariantDisabled = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantDisabled);

        $this->assertEquals(true, $productVariantEnabled->isEnabled());
        $this->assertEquals(false, $productVariantDisabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->getProduct()->isEnabled());
    }

    /**
     * @test
     */
    public function it_does_not_disable_the_product_variants_on_sylius_when_they_exist_on_akeneo(): void
    {
        $this->reconciler->reconcile(['23423545', '567567']);

        $productVariantEnabled = $this->productVariantRepository->findOneBy(['code' => '23423545']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantEnabled);
        $productVariantDisabled = $this->productVariantRepository->findOneBy(['code' => '567567']);
        $this->assertInstanceOf(ProductVariantInterface::class, $productVariantDisabled);

        $this->assertEquals(true, $productVariantEnabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->isEnabled());
        $this->assertEquals(true, $productVariantDisabled->getProduct()->isEnabled());
    }
}
