<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

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

    public function __construct(
        FactoryInterface $productOptionFactory,
        ProductOptionRepositoryInterface $productOptionRepository
    ) {
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionRepository = $productOptionRepository;
    }

    public function handle(ProductInterface $product, array $familyVariant)
    {
        foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $position => $axe) {
            if ($this->optionExists($product, $axe)) {
                continue;
            }
            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $axe]);
            if (!$productOption) {
                $productOption = $this->productOptionFactory->createNew();
                $productOption->setCode($axe);
                $productOption->setPosition($position);
                // TODO load attribute by $axe from API
                $attributeResponse = json_decode(file_get_contents(__DIR__ . '/../../attribute.json'), true);
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
