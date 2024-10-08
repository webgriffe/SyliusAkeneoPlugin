<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}

if (!interface_exists(\Sylius\Resource\Factory\FactoryInterface::class)) {
    class_alias(\Sylius\Resource\Factory\FactoryInterface::class, \Sylius\Component\Resource\Factory\FactoryInterface::class);
}
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
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Attribute\Importer as AttributeImporter;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductAttributeHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionValueHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\SyliusProductAttributeHelperTrait;
use Webmozart\Assert\Assert;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
final class Importer implements ImporterInterface
{
    use ProductOptionHelperTrait,
        ProductOptionValueHelperTrait,
        ProductAttributeHelperTrait,
        SyliusProductAttributeHelperTrait;

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
            $this->filterBySyliusSelectAttributeCodes($akeneoAttributes),
            $this->filterSyliusOptionCodes($akeneoAttributes),
        );
    }

    /**
     * @param AkeneoAttribute $akeneoAttribute
     */
    private function importOptionValues(array $akeneoAttribute, ProductOptionInterface $option): void
    {
        if ($akeneoAttribute['type'] !== AttributeImporter::SIMPLESELECT_TYPE &&
            $akeneoAttribute['type'] !== AttributeImporter::MULTISELECT_TYPE &&
            $akeneoAttribute['type'] !== AttributeImporter::BOOLEAN_TYPE
        ) {
            return;
        }
        $attributeCode = $akeneoAttribute['code'];

        if ($akeneoAttribute['type'] === AttributeImporter::BOOLEAN_TYPE) {
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

    private function getProductOptionRepository(): ?ProductOptionRepositoryInterface
    {
        return $this->optionRepository;
    }

    /**
     * @return RepositoryInterface<ProductAttributeInterface>
     */
    private function getProductAttributeRepository(): RepositoryInterface
    {
        return $this->attributeRepository;
    }
}
