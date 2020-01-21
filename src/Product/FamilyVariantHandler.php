<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\FamilyVariantHandlerInterface;
use Webmozart\Assert\Assert;

final class FamilyVariantHandler implements FamilyVariantHandlerInterface
{
    /** @var FactoryInterface */
    private $productOptionFactory;

    /** @var ProductOptionRepositoryInterface */
    private $productOptionRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    public function __construct(
        FactoryInterface $productOptionFactory,
        ProductOptionRepositoryInterface $productOptionRepository,
        ApiClientInterface $apiClient
    ) {
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionRepository = $productOptionRepository;
        $this->apiClient = $apiClient;
    }

    public function handle(ProductInterface $product, array $familyVariant): void
    {
        foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $position => $attributeCode) {
            if ($this->optionExists($product, $attributeCode)) {
                continue;
            }
            /** @var ProductOptionInterface|null $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);
            if (!$productOption) {
                $productOption = $this->productOptionFactory->createNew();
                Assert::isInstanceOf($productOption, ProductOptionInterface::class);
                $productOption->setCode($attributeCode);
                $productOption->setPosition($position);
                $attributeResponse = $this->apiClient->findAttribute($attributeCode);
                if ($attributeResponse === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Cannot handle variant family for product "%s", attribute "%s" does not exists on Akeneo.',
                            $product->getCode(),
                            $attributeCode
                        )
                    );
                }
                foreach ($attributeResponse['labels'] as $locale => $label) {
                    $productOptionTranslation = $productOption->getTranslation($locale);
                    if ($productOptionTranslation->getLocale() === $locale) {
                        $productOptionTranslation->setName($label);
                    }
                }
            }
            $this->productOptionRepository->add($productOption);
            $product->addOption($productOption);
        }
    }

    private function optionExists(ProductInterface $product, string $axe): bool
    {
        foreach ($product->getOptions() as $option) {
            if ($option->getCode() === $axe) {
                return true;
            }
        }

        return false;
    }
}
