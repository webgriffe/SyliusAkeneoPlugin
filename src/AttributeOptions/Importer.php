<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class Importer implements ImporterInterface
{
    private const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    private const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var RepositoryInterface */
    private $attributeRepository;

    public function __construct(ApiClientInterface $apiClient, RepositoryInterface $attributeRepository)
    {
        $this->apiClient = $apiClient;
        $this->attributeRepository = $attributeRepository;
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

        $attributeOptions = $this->apiClient->findAllAttributeOptions($identifier);
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices($attributeOptions);
        $attribute->setConfiguration($configuration);

        $this->attributeRepository->add($attribute);
    }

    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        // It's not possible to fetch only attributes or attribute options modified since a given date with the Akeneo
        // REST API. So, the $sinceDate argument it's not used.
        $attributes = $this->apiClient->findAllAttributes();
        $identifiers = [];
        foreach ($attributes as $attribute) {
            if ($attribute['type'] !== self::SIMPLESELECT_TYPE && $attribute['type'] !== self::MULTISELECT_TYPE) {
                continue;
            }
            $identifiers[] = $attribute['code'];
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
