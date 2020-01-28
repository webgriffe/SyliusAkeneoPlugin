<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Webmozart\Assert\Assert;

final class ProductContext implements Context
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
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
        string $relatedProductCode,
        string $associationTypeCode
    ) {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
        $associations = $product->getAssociations();
        Assert::count($associations, 2);
    }
}
