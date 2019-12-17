<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;

final class FamilyVariantHandler implements FamilyVariantHandlerInterface
{
    /**
     * @var FactoryInterface
     */
    private $productOptionFactory;
    /**
     * @var ProductOptionRepositoryInterface
     */
    private $productOptionRepository;
    /**
     * @var ApiClientInterface
     */
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

    public function handle(ProductInterface $product, array $familyVariant)
    {
        foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $position => $attributeCode) {
            if ($this->optionExists($product, $attributeCode)) {
                continue;
            }
            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);
            if (!$productOption) {
                $productOption = $this->productOptionFactory->createNew();
                $productOption->setCode($attributeCode);
                $productOption->setPosition($position);
                $attributeResponse = $this->apiClient->findAttribute($attributeCode);
                foreach ($attributeResponse['labels'] as $locale => $label) {
                    $productOption->getTranslation($locale)->setName($label);
                }
            }
            $product->addOption($productOption);
        }
    }

    private function optionExists(ProductInterface $product, $axe): bool
    {
        foreach ($product->getOptions() as $option) {
            if ($option->getCode() === $axe) {
                return true;
            }
        }
        return false;
    }
}
