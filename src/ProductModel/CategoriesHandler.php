<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductTaxonRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Webmozart\Assert\Assert;

final class CategoriesHandler implements CategoriesHandlerInterface
{
    /**
     * @var TaxonRepositoryInterface
     */
    private $taxonRepository;
    /**
     * @var ProductTaxonRepositoryInterface
     */
    private $productTaxonRepository;
    /**
     * @var FactoryInterface
     */
    private $productTaxonFactory;

    public function __construct(
        TaxonRepositoryInterface $taxonRepository,
        ProductTaxonRepositoryInterface $productTaxonRepository,
        FactoryInterface $productTaxonFactory
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->productTaxonRepository = $productTaxonRepository;
        $this->productTaxonFactory = $productTaxonFactory;
    }

    public function handle(ProductInterface $product, array $categories)
    {
        foreach ($categories as $category) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->taxonRepository->findOneBy(['code' => $category]);
            if ($taxon) {
                $productTaxon = $this->productTaxonRepository->findOneByProductCodeAndTaxonCode(
                    $product->getCode(),
                    $taxon->getCode()
                );
                if ($productTaxon) {
                    return;
                }
                /** @var ProductTaxonInterface $productTaxon */
                $productTaxon = $this->productTaxonFactory->createNew();
                Assert::isInstanceOf($productTaxon, ProductTaxonInterface::class);
                $productTaxon->setProduct($product);
                $productTaxon->setTaxon($taxon);
                $productTaxon->setPosition(0);
                $product->addProductTaxon($productTaxon);
            }
        }
    }
}
