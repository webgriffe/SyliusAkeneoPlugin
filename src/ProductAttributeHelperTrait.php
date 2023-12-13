<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Attribute\Importer as AttributeImporter;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
trait ProductAttributeHelperTrait
{
    abstract private function getAkeneoPimClient(): AkeneoPimClientInterface;

    private function importAttributeConfiguration(string $attributeCode, ProductAttributeInterface $attribute): void
    {
        /** @var array{choices: array<string, array<string, string>>, multiple: bool, min: ?int, max: ?int} $configuration */
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices(
            $this->getSortedAkeneoAttributeOptionsByAttributeCode($attributeCode),
        );
        $attribute->setConfiguration($configuration);

        // Do not flush any change here, otherwise we will cause a potential MySQL error irreversible.
    }

    /**
     * @return RepositoryInterface<ProductAttributeInterface>
     */
    abstract private function getProductAttributeRepository(): RepositoryInterface;

    /**
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius attribute
     *
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusAttributeCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusSelectAttributes = $this->getProductAttributeRepository()->findBy(['type' => SelectAttributeType::TYPE]);
        $syliusSelectAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusSelectAttributes,
            ),
        );
        $attributeCodes = [];
        /** @var AkeneoAttribute $akeneoAttribute */
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['code'], $syliusSelectAttributes, true)) {
                continue;
            }
            if ($akeneoAttribute['type'] !== AttributeImporter::SIMPLESELECT_TYPE && $akeneoAttribute['type'] !== AttributeImporter::MULTISELECT_TYPE) {
                continue;
            }
            $attributeCodes[] = $akeneoAttribute['code'];
        }
        usort(
            $attributeOptionsOrdered,
            static fn (array $option1, array $option2): int => $option1['sort_order'] <=> $option2['sort_order'],
        );

        return $attributeCodes;
    }


    /**
     * @param array<array-key, AkeneoAttributeOption> $attributeOptions
     *
     * @return array<string, array<string, string>>
     */
    private function convertAkeneoAttributeOptionsIntoSyliusChoices(array $attributeOptions): array
    {
        $choices = [];
        foreach ($attributeOptions as $attributeOption) {
            $attributeOptionLabelsNotNull = array_filter(
                $attributeOption['labels'],
                static fn (?string $label): bool => $label !== null,
            );
            $choices[$attributeOption['code']] = $attributeOptionLabelsNotNull;
        }

        return $choices;
    }

    /**
     * @return array<array-key, AkeneoAttributeOption>
     */
    private function getSortedAkeneoAttributeOptionsByAttributeCode(string $attributeCode): array
    {
        $attributeOptionsOrdered = [];
        /**
         * @psalm-suppress TooManyTemplateParams
         *
         * @var ResourceCursorInterface<array-key, AkeneoAttributeOption> $attributeOptions
         */
        $attributeOptions = $this->getAkeneoPimClient()->getAttributeOptionApi()->all($attributeCode);
        /** @var AkeneoAttributeOption $attributeOption */
        foreach ($attributeOptions as $attributeOption) {
            $attributeOptionsOrdered[] = $attributeOption;
        }
        usort(
            $attributeOptionsOrdered,
            static fn (array $option1, array $option2): int => $option1['sort_order'] <=> $option2['sort_order'],
        );

        return $attributeOptionsOrdered;
    }
}
