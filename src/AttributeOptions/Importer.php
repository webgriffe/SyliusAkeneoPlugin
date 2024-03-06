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
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductAttributeHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionValueHelperTrait;
use Webmozart\Assert\Assert;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
final class Importer implements ImporterInterface
{
    use ProductOptionHelperTrait, ProductOptionValueHelperTrait, ProductAttributeHelperTrait;

    private const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    private const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    private const BOOLEAN_TYPE = 'pim_catalog_boolean';

    private const METRIC_TYPE = 'pim_catalog_metric';

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
        private ?TranslatorInterface $translator = null,
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
        if ($this->translator === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                'v2.2.0',
                'Not passing a "%s" instance to "%s" constructor is deprecated and will not be possible anymore in the next major version.',
                TranslatorInterface::class,
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
        /** @var AkeneoAttribute $attributeResponse */
        $attributeResponse = $this->apiClient->getAttributeApi()->get($identifier);

        // TODO: Update also the position of the option? The problem is that this position is on family variant entity!
        $this->importProductOptionTranslations($attributeResponse, $option);
        $this->importOptionValues($attributeResponse, $option);
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
            if (!in_array($akeneoAttribute['type'], [self::SIMPLESELECT_TYPE, self::MULTISELECT_TYPE, self::BOOLEAN_TYPE, self::METRIC_TYPE], true)) {
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
    private function importOptionValues(array $akeneoAttribute, ProductOptionInterface $option): void
    {
        if ($akeneoAttribute['type'] !== self::SIMPLESELECT_TYPE &&
            $akeneoAttribute['type'] !== self::MULTISELECT_TYPE &&
            $akeneoAttribute['type'] !== self::BOOLEAN_TYPE
        ) {
            return;
        }
        $attributeCode = $akeneoAttribute['code'];

        if ($akeneoAttribute['type'] === self::BOOLEAN_TYPE) {
            foreach ([true, false] as $booleanValue) {
                $optionValueCode = $this->getSyliusProductOptionValueCode($attributeCode, (string) $booleanValue);
                $productOptionValue = $this->getProductOptionValueFromOption($option, $optionValueCode);
                if ($productOptionValue === null) {
                    $productOptionValue = $this->createNewProductOptionValue($optionValueCode, $option);
                }
                $this->addBooleanProductOptionValueTranslations($booleanValue, $productOptionValue);
            }

            return;
        }
        $attributeOptions = $this->getSortedAkeneoAttributeOptionsByAttributeCode($attributeCode);

        foreach ($attributeOptions as $attributeOption) {
            $optionValueCode = $this->getSyliusProductOptionValueCode($attributeCode, $attributeOption['code']);
            $optionValue = $this->getProductOptionValueFromOption($option, $optionValueCode);
            if ($optionValue === null) {
                $optionValue = $this->createNewProductOptionValue($optionValueCode, $option);
            }
            $this->importSelectProductOptionValueTranslations($attributeOption, $optionValue);
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
     * @return FactoryInterface<ProductOptionTranslationInterface>
     */
    private function getProductOptionTranslationFactory(): FactoryInterface
    {
        $productOptionTranslationFactory = $this->productOptionTranslationFactory;
        Assert::isInstanceOf($productOptionTranslationFactory, FactoryInterface::class);

        return $productOptionTranslationFactory;
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

    /**
     * This method should be called only if the productOptionRepository is injected, so we can assume
     * that this factory is injected too.
     *
     * @return FactoryInterface<ProductOptionValueInterface>
     */
    private function getProductOptionValueFactory(): FactoryInterface
    {
        $productOptionValueFactory = $this->productOptionValueFactory;
        Assert::isInstanceOf($productOptionValueFactory, FactoryInterface::class);

        return $productOptionValueFactory;
    }

    /**
     * This method should be called only if the productOptionRepository is injected, so we can assume
     * that this translator is injected too.
     */
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->translator;
        Assert::isInstanceOf($translator, TranslatorInterface::class);

        return $translator;
    }

    private function getProductOptionValueFromOption(
        ProductOptionInterface $option,
        string $optionValueCode,
    ): ?ProductOptionValueInterface {
        $productOptionValue = null;
        foreach ($option->getValues() as $value) {
            if ($value->getCode() === $optionValueCode) {
                $productOptionValue = $value;

                break;
            }
        }

        return $productOptionValue;
    }

    private function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        return $this->apiClient;
    }
}
