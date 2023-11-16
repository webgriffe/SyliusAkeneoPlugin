<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

/**
 * @phpstan-type AkeneoAttribute array{code: string, type: string}
 * @phpstan-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, string>}
 */
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
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return 'AttributeOptions';
    }

    public function import(string $identifier): void
    {
        $attribute = $this->attributeRepository->findOneBy(['code' => $identifier]);
        if (null !== $attribute && $attribute->getType() === SelectAttributeType::TYPE) {
            $this->importAttribute($identifier, $attribute);
        }
    }

    /**
     * As stated at https://api.akeneo.com/documentation/filter.html#by-update-date-3:
     *
     * > For Simple select and Multiple select attribute, an option update isn't considered as an attribute update.
     *
     * So, the $sinceDate argument it's not used here.
     */
    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $this->eventDispatcher->dispatch(
            new IdentifiersModifiedSinceSearchBuilderBuiltEvent($this, $searchBuilder, $sinceDate),
        );
        /** @var ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes */
        $akeneoAttributes = $this->apiClient->getAttributeApi()->all(50, ['search' => $searchBuilder->getFilters()]);

        return $this->filterBySyliusAttributeCodes($akeneoAttributes);
    }

    /**
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius attribute
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusAttributeCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusSelectAttributes = $this->attributeRepository->findBy(['type' => SelectAttributeType::TYPE]);
        $syliusSelectAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusSelectAttributes,
            ),
        );
        $attributeCodes = [];
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['code'], $syliusSelectAttributes, true)) {
                continue;
            }
            if ($akeneoAttribute['type'] !== self::SIMPLESELECT_TYPE && $akeneoAttribute['type'] !== self::MULTISELECT_TYPE) {
                continue;
            }
            $attributeCodes[] = $akeneoAttribute['code'];
        }

        return $attributeCodes;
    }

    private function importAttribute(string $attributeCode, ProductAttributeInterface $attribute): void
    {
        $attributeOptionsOrdered = [];
        /** @var ResourceCursorInterface<array-key, AkeneoAttributeOption> $attributeOptions */
        $attributeOptions = $this->apiClient->getAttributeOptionApi()->all($attributeCode);
        foreach ($attributeOptions as $attributeOption) {
            $attributeOptionsOrdered[] = $attributeOption;
        }
        usort(
            $attributeOptionsOrdered,
            static fn (array $option1, array $option2): int => $option1['sort_order'] <=> $option2['sort_order'],
        );
        /** @var array{choices: array<string, array<string, string>>, multiple: bool, min: ?int, max: ?int} $configuration */
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices($attributeOptionsOrdered);
        $attribute->setConfiguration($configuration);

        $this->attributeRepository->add($attribute);
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
            $choices[$attributeOption['code']] = $attributeOption['labels'];
        }

        return $choices;
    }
}
