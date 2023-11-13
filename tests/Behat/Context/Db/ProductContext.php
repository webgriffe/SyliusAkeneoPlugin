<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webmozart\Assert\Assert;

final class ProductContext implements Context
{
    /**
     * @param RepositoryInterface<ProductAssociationInterface> $productAssociationRepository
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $productVariantRepository,
        private RepositoryInterface $productAssociationRepository,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
    ) {
    }

    /**
     * @Then the product :code should exist
     */
    public function theProductShouldExistsWithTheRightData(string $code): void
    {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
    }

    /**
     * @Given the product variant :code of product :productCode should exist
     */
    public function theProductVariantShouldExistsWithTheRightData(string $code, string $productCode): void
    {
        $product = $this->productVariantRepository->findOneByCodeAndProductCode($code, $productCode);
        Assert::isInstanceOf($product, ProductVariantInterface::class);
    }

    /**
     * @Then the product :code should be associated to product :associatedProductCode for association with code :associationTypeCode
     */
    public function theProductShouldBeAssociatedToProductForAssociationWithCode(
        string $code,
        string $associatedProductCode,
        string $associationTypeCode,
    ): void {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);

        $associatedProduct = $this->productRepository->findOneByCode($associatedProductCode);
        Assert::isInstanceOf($associatedProduct, ProductInterface::class);

        /** @var ProductAssociationTypeInterface|mixed|object|null $productAssociationType */
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $associationTypeCode]);
        Assert::isInstanceOf($productAssociationType, ProductAssociationTypeInterface::class);
        $productAssociation = $this->productAssociationRepository->findOneBy(
            ['owner' => $product, 'type' => $productAssociationType],
        );
        Assert::isInstanceOf($productAssociation, ProductAssociationInterface::class);
        $associatedProducts = $productAssociation->getAssociatedProducts();
        Assert::true($associatedProducts->contains($associatedProduct));
    }

    /**
     * @Then /^the ("[^"]+" product) should be enabled$/
     */
    public function theProductShouldBeEnabled(ProductInterface $product): void
    {
        Assert::true($product->isEnabled());
    }

    /**
     * @Given /^the ("[^"]+" product variant) should be enabled$/
     */
    public function theProductVariantShouldBeEnabled(ProductVariantInterface $productVariant): void
    {
        Assert::true($productVariant->isEnabled());
    }

    /**
     * @Then /^the ("[^"]+" product) should be disabled/
     */
    public function theProductShouldBeDisabled(ProductInterface $product): void
    {
        Assert::false($product->isEnabled());
    }

    /**
     * @Given /^the ("[^"]+" product variant) should be disabled/
     */
    public function theProductVariantShouldBeDisabled(ProductVariantInterface $productVariant): void
    {
        Assert::false($productVariant->isEnabled());
    }
}
