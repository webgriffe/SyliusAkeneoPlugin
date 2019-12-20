<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Webmozart\Assert\Assert;

final class ProductContext implements Context
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Then /^the product "([^"]*)" should exists with the right data$/
     */
    public function theProductShouldExistsWithTheRightData(string $code)
    {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
    }
}
