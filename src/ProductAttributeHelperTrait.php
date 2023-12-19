<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

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
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius SELECT attribute
     *
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusSelectAttributeCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusSelectAttributes = $this->getProductAttributeRepository()->findBy(['type' => SelectAttributeType::TYPE]);

        return $this->filterBySyliusAttributes($syliusSelectAttributes, $akeneoAttributes);
    }

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
        $syliusAttributes = $this->getProductAttributeRepository()->findAll();

        return $this->filterBySyliusAttributes($syliusAttributes, $akeneoAttributes);
    }

    /**
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ProductAttributeInterface[] $syliusAttributes
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusAttributes(array $syliusAttributes, ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusAttributes,
            ),
        );
        $attributeCodes = [];
        /** @var AkeneoAttribute $akeneoAttribute */
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['code'], $syliusAttributes, true)) {
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
