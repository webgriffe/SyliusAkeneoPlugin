<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
trait ProductOptionValueHelperTrait
{
    /**
     * @return string[]
     */
    abstract private function getDefinedLocaleCodes(): array;

    /**
     * @return FactoryInterface<ProductOptionValueTranslationInterface>
     */
    abstract private function getProductOptionValueTranslationFactory(): FactoryInterface;

    /**
     * @return FactoryInterface<ProductOptionValueInterface>
     */
    abstract private function getProductOptionValueFactory(): FactoryInterface;

    protected function getSyliusProductOptionValueCode(string ...$pieces): string
    {
        $slugifiedPieces = array_map(static function (string $word): string {
            return str_replace(['.', ','], '', $word);
        }, $pieces);

        return implode('_', $slugifiedPieces);
    }

    /**
     * @param AkeneoAttributeOption $akeneoAttributeOption
     */
    protected function importProductOptionValueTranslations(array $akeneoAttributeOption, ProductOptionValueInterface $optionValue): void
    {
        $productOptionValueTranslationFactory = $this->getProductOptionValueTranslationFactory();

        foreach ($akeneoAttributeOption['labels'] as $localeCode => $label) {
            if (!in_array($localeCode, $this->getDefinedLocaleCodes(), true)) {
                continue;
            }
            $productOptionValueTranslation = $optionValue->getTranslation($localeCode);
            if ($productOptionValueTranslation->getLocale() !== $localeCode) {
                $productOptionValueTranslation = $productOptionValueTranslationFactory->createNew();
                $productOptionValueTranslation->setLocale($localeCode);
            }
            $productOptionValueTranslation->setValue($label ?? $optionValue->getCode());
            if (!$optionValue->hasTranslation($productOptionValueTranslation)) {
                $optionValue->addTranslation($productOptionValueTranslation);
            }
        }
    }

    private function createNewProductOptionValue(
        string $optionValueCode,
        ProductOptionInterface $productOption,
    ): ProductOptionValueInterface {
        $optionValue = $this->getProductOptionValueFactory()->createNew();
        $optionValue->setCode($optionValueCode);
        $optionValue->setOption($productOption);
        $productOption->addValue($optionValue);

        return $optionValue;
    }
}
