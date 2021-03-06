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
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var RepositoryInterface */
    private $productAssociationRepository;

    /** @var ProductAssociationTypeRepositoryInterface */
    private $productAssociationTypeRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        RepositoryInterface $productAssociationRepository,
        ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->productAssociationRepository = $productAssociationRepository;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
    }

    /**
     * @Then /^the product "([^"]*)" should exists with the right data$/
     */
    public function theProductShouldExistsWithTheRightData(string $code)
    {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
    }

    /**
     * @Given /^the product variant "([^"]*)" of product "([^"]*)" should exists with the right data$/
     */
    public function theProductVariantShouldExistsWithTheRightData(string $code, string $productCode)
    {
        $product = $this->productVariantRepository->findOneByCodeAndProductCode($code, $productCode);
        Assert::isInstanceOf($product, ProductVariantInterface::class);
    }

    /**
     * @Then /^the product "([^"]*)" should not exists$/
     */
    public function theProductShouldNotExists(string $code)
    {
        $product = $this->productRepository->findOneByCode($code);
        Assert::null($product);
    }

    /**
     * @Then /^the product "([^"]*)" should be associated to product "([^"]*)" for association with code "([^"]*)"$/
     */
    public function theProductShouldBeAssociatedToProductForAssociationWithCode(
        string $code,
        string $associatedProductCode,
        string $associationTypeCode
    ) {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);

        $associatedProduct = $this->productRepository->findOneByCode($associatedProductCode);
        Assert::isInstanceOf($associatedProduct, ProductInterface::class);

        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $associationTypeCode]);
        Assert::isInstanceOf($productAssociationType, ProductAssociationTypeInterface::class);
        /** @var ProductAssociationTypeInterface $productAssociationType */
        $productAssociation = $this->productAssociationRepository->findOneBy(
            ['owner' => $product, 'type' => $productAssociationType]
        );
        Assert::isInstanceOf($productAssociation, ProductAssociationInterface::class);
        /** @var ProductAssociationInterface $productAssociation */
        $associatedProducts = $productAssociation->getAssociatedProducts();
        Assert::true($associatedProducts->contains($associatedProduct));
    }

    /**
     * @Then /^the ("[^"]+" product) should be enabled$/
     */
    public function theProductShouldBeEnabled(ProductInterface $product)
    {
        Assert::true($product->isEnabled());
    }

    /**
     * @Given /^the ("[^"]+" product variant) should be enabled$/
     */
    public function theProductVariantShouldBeEnabled(ProductVariantInterface $productVariant)
    {
        Assert::true($productVariant->isEnabled());
    }

    /**
     * @Then /^the ("[^"]+" product) should be disabled/
     */
    public function theProductShouldBeDisabled(ProductInterface $product)
    {
        Assert::false($product->isEnabled());
    }

    /**
     * @Given /^the ("[^"]+" product variant) should be disabled/
     */
    public function theProductVariantShouldBeDisabled(ProductVariantInterface $productVariant)
    {
        Assert::false($productVariant->isEnabled());
    }
}
