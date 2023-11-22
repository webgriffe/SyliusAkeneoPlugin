<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
trait ProductOptionHelperTrait
{
    /**
     * @return FactoryInterface<ProductOptionTranslationInterface>
     */
    abstract private function getProductOptionTranslationFactory(): FactoryInterface;

    /**
     * @param AkeneoAttribute $akeneoAttribute
     */
    private function importProductOptionTranslations(array $akeneoAttribute, ProductOptionInterface $productOption): void
    {
        foreach ($akeneoAttribute['labels'] as $locale => $label) {
            $productOptionTranslation = $productOption->getTranslation($locale);
            if ($productOptionTranslation->getLocale() === $locale) {
                $productOptionTranslation->setName($label);

                continue;
            }
            $newProductOptionTranslation = $this->getProductOptionTranslationFactory()->createNew();
            $newProductOptionTranslation->setLocale($locale);
            $newProductOptionTranslation->setName($label);
            $productOption->addTranslation($newProductOptionTranslation);
        }
    }
}
