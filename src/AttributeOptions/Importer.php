<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionHelperTrait;
use Webmozart\Assert\Assert;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
final class Importer implements ImporterInterface
{
    use ProductOptionHelperTrait;

    private const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    private const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    /**
     * @param RepositoryInterface<ProductAttributeInterface> $attributeRepository
     * @param ?FactoryInterface<ProductOptionValueTranslationInterface> $productOptionValueTranslationFactory
     * @param ?FactoryInterface<ProductOptionValueInterface> $productOptionValueFactory
     * @param ?FactoryInterface<ProductOptionTranslationInterface> $productOptionTranslationFactory
     */
    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private RepositoryInterface $attributeRepository,
        private EventDispatcherInterface $eventDispatcher,
        private ?ProductOptionRepositoryInterface $optionRepository = null,
        private ?TranslationLocaleProviderInterface $translationLocaleProvider = null,
        private ?FactoryInterface $productOptionValueTranslationFactory = null,
        private ?FactoryInterface $productOptionValueFactory = null,
        private ?FactoryInterface $productOptionTranslationFactory = null,
    ) {
        if ($this->optionRepository === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                'v2.2.0',
                'Not passing a "%s" instance to "%s" constructor is deprecated and will not be possible anymore in the next major version.',
                ProductOptionRepositoryInterface::class,
                self::class,
            );
        }
        if ($this->translationLocaleProvider === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                'v2.2.0',
                'Not passing a "%s" instance to "%s" constructor is deprecated and will not be possible anymore in the next major version.',
                TranslationLocaleProviderInterface::class,
                self::class,
            );
        }
        if ($this->productOptionValueTranslationFactory === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                'v2.2.0',
                'Not passing a "%s" instance to "%s" constructor is deprecated and will not be possible anymore in the next major version.',
                FactoryInterface::class,
                self::class,
            );
        }
    }

    public function getAkeneoEntity(): string
    {
        return 'AttributeOptions';
    }

    public function import(string $identifier): void
    {
        $attribute = $this->attributeRepository->findOneBy(['code' => $identifier]);
        if (null !== $attribute && $attribute->getType() === SelectAttributeType::TYPE) {
            $this->importAttributeConfiguration($identifier, $attribute);
        }
        $optionRepository = $this->optionRepository;
        if (!$optionRepository instanceof ProductOptionRepositoryInterface) {
            return;
        }
        $option = $optionRepository->findOneBy(['code' => $identifier]);
        if (!$option instanceof ProductOptionInterface) {
            return;
        }
        $this->updateProductOption($option);
        $this->importOptionValues($identifier, $option);
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
        /**
         * @psalm-suppress TooManyTemplateParams
         *
         * @var ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
         */
        $akeneoAttributes = $this->apiClient->getAttributeApi()->all(50, ['search' => $searchBuilder->getFilters()]);

        return array_merge(
            $this->filterBySyliusAttributeCodes($akeneoAttributes),
            $this->filterSyliusOptionCodes($akeneoAttributes),
        );
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
        $syliusSelectAttributes = $this->attributeRepository->findBy(['type' => SelectAttributeType::TYPE]);
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
            if ($akeneoAttribute['type'] !== self::SIMPLESELECT_TYPE && $akeneoAttribute['type'] !== self::MULTISELECT_TYPE) {
                continue;
            }
            $attributeCodes[] = $akeneoAttribute['code'];
        }

        return $attributeCodes;
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
    private function filterSyliusOptionCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $productOptionRepository = $this->optionRepository;
        if (!$productOptionRepository instanceof ProductOptionRepositoryInterface) {
            return [];
        }
        $akeneoAttributeCodes = [];
        /** @var AkeneoAttribute $akeneoAttribute */
        foreach ($akeneoAttributes as $akeneoAttribute) {
            $akeneoAttributeCodes[] = $akeneoAttribute['code'];
        }
        $syliusOptions = $productOptionRepository->findByCodes($akeneoAttributeCodes);

        return array_map(
            static fn (ProductOptionInterface $option): string => (string) $option->getCode(),
            $syliusOptions,
        );
    }

    private function importAttributeConfiguration(string $attributeCode, ProductAttributeInterface $attribute): void
    {
        /** @var array{choices: array<string, array<string, string>>, multiple: bool, min: ?int, max: ?int} $configuration */
        $configuration = $attribute->getConfiguration();
        $configuration['choices'] = $this->convertAkeneoAttributeOptionsIntoSyliusChoices(
            $this->getSortedAkeneoAttributeOptionsByAttributeCode($attributeCode),
        );
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
            $attributeOptionLabelsNotNull = array_filter(
                $attributeOption['labels'],
                static fn (?string $label): bool => $label !== null,
            );
            $choices[$attributeOption['code']] = $attributeOptionLabelsNotNull;
        }

        return $choices;
    }

    private function importOptionValues(string $attributeCode, ProductOptionInterface $option): void
    {
        $attributeOptions = $this->getSortedAkeneoAttributeOptionsByAttributeCode($attributeCode);

        foreach ($attributeOptions as $attributeOption) {
            $optionValueCode = $this->getSyliusProductOptionValueCode($attributeCode, $attributeOption['code']);
            $optionValue = null;
            foreach ($option->getValues() as $value) {
                if ($value->getCode() === $optionValueCode) {
                    $optionValue = $value;

                    break;
                }
            }
            if ($optionValue === null) {
                // We can assume that if we are here is because the option repository has been injected, so event this factory should be!
                $productOptionValueFactory = $this->productOptionValueFactory;
                Assert::isInstanceOf($productOptionValueFactory, FactoryInterface::class);
                $optionValue = $productOptionValueFactory->createNew();
                // TODO handle translations
                $optionValue->setCode($optionValueCode);
                $option->addValue($optionValue);
            }

            // We can assume that if we are here is because the option repository has been injected, so event these services should be!
            $productOptionValueTranslationFactory = $this->productOptionValueTranslationFactory;
            Assert::isInstanceOf($productOptionValueTranslationFactory, FactoryInterface::class);

            $this->importProductOptionValueTranslations($attributeOption, $optionValue);
        }
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
        $attributeOptions = $this->apiClient->getAttributeOptionApi()->all($attributeCode);
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

    private function updateProductOption(ProductOptionInterface $productOption): void
    {
        // TODO: Update also the position of the option? The problem is that this position is on family variant entity!
        $productOptionCode = $productOption->getCode();
        Assert::notNull($productOptionCode);

        // We can assume that if we are here is because the option repository has been injected, so event this factory should be!
        $productOptionTranslationFactory = $this->productOptionTranslationFactory;
        Assert::isInstanceOf($productOptionTranslationFactory, FactoryInterface::class);

        /** @var AkeneoAttribute $attributeResponse */
        $attributeResponse = $this->apiClient->getAttributeApi()->get($productOptionCode);
        foreach ($attributeResponse['labels'] as $locale => $label) {
            $productOptionTranslation = $productOption->getTranslation($locale);
            if ($productOptionTranslation->getLocale() === $locale) {
                $productOptionTranslation->setName($label);

                continue;
            }
            $newProductOptionTranslation = $productOptionTranslationFactory->createNew();
            $newProductOptionTranslation->setLocale($locale);
            $newProductOptionTranslation->setName($label);
            $productOption->addTranslation($newProductOptionTranslation);
        }
    }

    /**
     * This method should be called only if the productOptionRepository is injected, so we can assume
     * that this factory is injected too.
     */
    private function getDefinedLocaleCodes(): array
    {
        $translationLocaleProvider = $this->translationLocaleProvider;
        Assert::isInstanceOf($translationLocaleProvider, TranslationLocaleProviderInterface::class);

        return $translationLocaleProvider->getDefinedLocalesCodes();
    }

    /**
     * This method should be called only if the productOptionRepository is injected, so we can assume
     * that this factory is injected too.
     *
     * @return FactoryInterface<ProductOptionValueTranslationInterface>
     */
    private function getProductOptionValueTranslationFactory(): FactoryInterface
    {
        $productOptionValueTranslationFactory = $this->productOptionValueTranslationFactory;
        Assert::isInstanceOf($productOptionValueTranslationFactory, FactoryInterface::class);

        return $productOptionValueTranslationFactory;
    }
}
