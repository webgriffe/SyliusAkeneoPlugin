<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class Importer implements ImporterInterface
{
    private const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    private const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    public function __construct(private ApiClientInterface $apiClient, private RepositoryInterface $attributeRepository)
    {
    }

    public function getAkeneoEntity(): string
    {
        return 'AttributeOptions';
    }

    public function import(string $identifier): void
    {
        /** @var ProductAttributeInterface|null $attribute */
        $attribute = $this->attributeRepository->findOneBy(['code' => $identifier]);
        if (null === $attribute) {
            return;
        }

        if ($attribute->getType() !== SelectAttributeType::TYPE) {
            return;
        }

        /** @var array[] $attributeOptions */
        $attributeOptions = $this->apiClient->findAllAttributeOptions($identifier);
        usort(
            $attributeOptions,
            static fn (array $option1, array $option2): int => ($option1['sort_order'] ?? 0) <=> ($option2['sort_order'] ?? 0)
        );
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices($attributeOptions);
        $attribute->setConfiguration($configuration);

        $this->attributeRepository->add($attribute);
    }

    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        // It's not possible to fetch only attributes or attribute options modified since a given date with the Akeneo
        // REST API. So, the $sinceDate argument it's not used.
        $akeneoAttributes = $this->apiClient->findAllAttributes();
        /** @var ProductAttributeInterface[] $syliusSelectAttributes */
        $syliusSelectAttributes = $this->attributeRepository->findBy(['type' => SelectAttributeType::TYPE]);
        $syliusSelectAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusSelectAttributes
            )
        );
        $identifiers = [];
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['code'], $syliusSelectAttributes, true)) {
                continue;
            }
            if ($akeneoAttribute['type'] !== self::SIMPLESELECT_TYPE && $akeneoAttribute['type'] !== self::MULTISELECT_TYPE) {
                continue;
            }
            $identifiers[] = $akeneoAttribute['code'];
        }

        return $identifiers;
    }

    private function convertAkeneoAttributeOptionsIntoSyliusChoices(array $attributeOptions): array
    {
        $choices = [];
        foreach ($attributeOptions as $attributeOption) {
            $choices[$attributeOption['code']] = $attributeOption['labels'];
        }

        return $choices;
    }
}
