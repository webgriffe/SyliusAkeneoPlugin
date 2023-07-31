<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class Importer implements ImporterInterface
{
    private const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    private const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    /**
     * @param RepositoryInterface<ProductAttributeInterface> $attributeRepository
     */
    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private RepositoryInterface $attributeRepository,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return 'AttributeOptions';
    }

    public function import(string $identifier): void
    {
        $attribute = $this->attributeRepository->findOneBy(['code' => $identifier]);
        if (null === $attribute) {
            return;
        }

        if ($attribute->getType() !== SelectAttributeType::TYPE) {
            return;
        }

        $attributeOptionsOrdered = [];
        $attributeOptions = $this->apiClient->getAttributeOptionApi()->all($identifier);
        /** @var array $attributeOption */
        foreach ($attributeOptions as $attributeOption) {
            $attributeOptionsOrdered[] = $attributeOption;
        }
        usort(
            $attributeOptionsOrdered,
            static fn (array $option1, array $option2): int => ($option1['sort_order'] ?? 0) <=> ($option2['sort_order'] ?? 0),
        );
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices($attributeOptionsOrdered);
        $attribute->setConfiguration($configuration);

        $this->attributeRepository->add($attribute);
    }

    /**
     * It's not possible to fetch only attributes or attribute options modified since a given date with the Akeneo
     * REST API. So, the $sinceDate argument it's not used.
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        /** @var array<array-key, array<string, mixed>> $akeneoAttributes */
        $akeneoAttributes = $this->apiClient->getAttributeApi()->all();
        $syliusSelectAttributes = $this->attributeRepository->findBy(['type' => SelectAttributeType::TYPE]);
        $syliusSelectAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusSelectAttributes,
            ),
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
