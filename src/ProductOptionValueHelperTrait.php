<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

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

    abstract private function getTranslator(): TranslatorInterface;

    private function getSyliusProductOptionValueCode(string ...$pieces): string
    {
        $slugifiedPieces = array_map(static function (string $word): string {
            return str_replace(['.', ','], '', $word);
        }, $pieces);

        return implode('_', $slugifiedPieces);
    }

    /**
     * @param AkeneoAttributeOption $akeneoAttributeOption
     */
    private function importProductOptionValueTranslations(
        array $akeneoAttributeOption,
        ProductOptionValueInterface $productOptionValue,
    ): void {
        foreach ($akeneoAttributeOption['labels'] as $localeCode => $label) {
            if (!in_array($localeCode, $this->getDefinedLocaleCodes(), true)) {
                continue;
            }
            $productOptionValueCode = $productOptionValue->getCode();
            Assert::string($productOptionValueCode);
            $this->addOrUpdateProductOptionValueTranslation(
                $productOptionValue,
                $localeCode,
                $label ?? $productOptionValueCode,
            );
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

    private function addBooleanProductOptionValueTranslations(
        bool $booleanValue,
        ProductOptionValueInterface $productOptionValue,
    ): void {
        $locales = $this->getDefinedLocaleCodes();
        foreach ($locales as $localeCode) {
            $valueTranslationKey = $booleanValue ? 'sylius.ui.yes_label' : 'sylius.ui.no_label';
            $valueTranslatedLabel = $this->getTranslator()->trans($valueTranslationKey, [], null, $localeCode);
            $productOptionValue = $this->addOrUpdateProductOptionValueTranslation(
                $productOptionValue,
                $localeCode,
                $valueTranslatedLabel,
            );
        }
    }

    private function addOrUpdateProductOptionValueTranslation(
        ProductOptionValueInterface $productOptionValue,
        string $localeCode,
        string $label,
    ): ProductOptionValueInterface {
        $productOptionValueTranslationFactory = $this->getProductOptionValueTranslationFactory();

        $productOptionValueTranslation = $productOptionValue->getTranslation($localeCode);
        if ($productOptionValueTranslation->getLocale() !== $localeCode) {
            $productOptionValueTranslation = $productOptionValueTranslationFactory->createNew();
            $productOptionValueTranslation->setLocale($localeCode);
        }
        $productOptionValueTranslation->setValue($label);
        if (!$productOptionValue->hasTranslation($productOptionValueTranslation)) {
            $productOptionValue->addTranslation($productOptionValueTranslation);
        }

        return $productOptionValue;
    }
}
