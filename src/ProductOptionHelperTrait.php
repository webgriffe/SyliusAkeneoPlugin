<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Attribute\Importer as AttributeImporter;

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

    abstract private function getProductOptionRepository(): ?ProductOptionRepositoryInterface;

    /**
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius attribute
     *
     * @psalm-suppress TooManyTemplateParams
     * @psalm-suppress UnnecessaryVarAnnotation
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterSyliusOptionCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        /** @var ?ProductOptionRepositoryInterface $productOptionRepository */
        $productOptionRepository = $this->getProductOptionRepository();
        if (!$productOptionRepository instanceof ProductOptionRepositoryInterface) {
            return [];
        }
        $akeneoAttributeCodes = [];
        /** @var AkeneoAttribute $akeneoAttribute */
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['type'], [
                    AttributeImporter::SIMPLESELECT_TYPE,
                    AttributeImporter::MULTISELECT_TYPE,
                    AttributeImporter::BOOLEAN_TYPE,
                    AttributeImporter::METRIC_TYPE,
                ], true)
            ) {
                continue;
            }
            $akeneoAttributeCodes[] = $akeneoAttribute['code'];
        }
        $syliusOptions = $productOptionRepository->findByCodes($akeneoAttributeCodes);

        return array_map(
            static fn (ProductOptionInterface $option): string => (string) $option->getCode(),
            $syliusOptions,
        );
    }

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
