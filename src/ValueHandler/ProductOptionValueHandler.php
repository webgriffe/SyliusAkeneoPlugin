<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ProductOptionValueHandler implements ValueHandlerInterface
{
    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ProductOptionRepositoryInterface */
    private $productOptionRepository;

    /** @var FactoryInterface */
    private $productOptionValueFactory;

    /** @var FactoryInterface */
    private $productOptionValueTranslationFactory;

    /** @var RepositoryInterface */
    private $productOptionValueRepository;

    /** @var TranslationLocaleProviderInterface|null */
    private $translationLocaleProvider;

    /** @var TranslatorInterface|null */
    private $translator;

    public function __construct(
        ApiClientInterface $apiClient,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        RepositoryInterface $productOptionValueRepository,
        TranslationLocaleProviderInterface $translationLocaleProvider = null,
        TranslatorInterface $translator = null
    ) {
        $this->apiClient = $apiClient;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productOptionValueTranslationFactory = $productOptionValueTranslationFactory;
        $this->productOptionValueRepository = $productOptionValueRepository;
        if ($translationLocaleProvider === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.6',
                'Not passing a translation locale provider to %s is deprecated and will not be possible anymore in %s',
                __CLASS__,
                '2.0'
            );
        }
        $this->translationLocaleProvider = $translationLocaleProvider;
        if ($translator === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.15',
                'Not passing a translator to %s is deprecated and will not be possible anymore in %s',
                __CLASS__,
                '2.0'
            );
        }
        $this->translator = $translator;
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $this->isVariantOption($subject, $attribute);
    }

    /**
     * @param mixed $productVariant
     */
    public function handle($productVariant, string $optionCode, array $akeneoValue): void
    {
        if (!$productVariant instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'This option value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($productVariant) ? get_class($productVariant) : gettype($productVariant)
                )
            );
        }
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        if (count($akeneoValue) > 1) {
            throw new RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". More than one value is set for this attribute on Akeneo but this handler only supports ' .
                    'single value for product options.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode
                )
            );
        }
        $akeneoAttribute = $this->apiClient->findAttribute($optionCode);
        if ($akeneoAttribute === null) {
            throw new RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". The attribute "%s" was not found on Akeneo.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode,
                    $optionCode
                )
            );
        }
        /** @var string|array|bool|int $akeneoValueData */
        $akeneoValueData = $akeneoValue[0]['data'];

        $productOption = $this->getProductOption($optionCode, $productVariant, $product);

        /** @var string $attributeType */
        $attributeType = $akeneoAttribute['type'];
        switch ($attributeType) {
            case 'pim_catalog_simpleselect':
                Assert::string($akeneoValueData);
                $this->handleSelectOption($productOption, $optionCode, $akeneoValueData, $product, $productVariant);

                break;
            case 'pim_catalog_metric':
                Assert::isArray($akeneoValueData);
                $this->handleMetricOption($productOption, $optionCode, $akeneoValueData, $product, $productVariant);

                break;
            case 'pim_catalog_boolean':
                Assert::boolean($akeneoValueData);
                $this->handleBooleanOption($productOption, $optionCode, $akeneoValueData, $product, $productVariant);

                break;
            default:
                throw new LogicException(sprintf('The Akeneo attribute type "%s" is not supported from the "%s"', $attributeType, self::class));
        }
    }

    private function isVariantOption(ProductVariantInterface $productVariant, string $attribute): bool
    {
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        foreach ($product->getOptions() as $option) {
            if ($attribute === $option->getCode()) {
                return true;
            }
        }

        return false;
    }

    private function handleSelectOption(ProductOptionInterface $productOption, string $optionCode, string $akeneoValue, ProductInterface $product, ProductVariantInterface $productVariant): void
    {
        $optionValueCode = $this->createOptionValueCode($optionCode, $akeneoValue);

        $optionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);

        $akeneoAttributeOption = $this->apiClient->findAttributeOption($optionCode, $akeneoValue);
        if ($akeneoAttributeOption === null) {
            throw new RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". The option value for this variant is "%s" but there is no such option on Akeneo.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode,
                    $akeneoValue
                )
            );
        }
        foreach ($akeneoAttributeOption['labels'] as $localeCode => $label) {
            if ($this->translationLocaleProvider !== null &&
                !in_array($localeCode, $this->translationLocaleProvider->getDefinedLocalesCodes(), true)) {
                continue;
            }
            $optionValueTranslation = $optionValue->getTranslation($localeCode);
            if ($optionValueTranslation->getLocale() !== $localeCode) {
                /** @var ProductOptionValueTranslationInterface $optionValueTranslation */
                $optionValueTranslation = $this->productOptionValueTranslationFactory->createNew();
                $optionValueTranslation->setLocale($localeCode);
            }
            $optionValueTranslation->setValue($label);
            if (!$optionValue->hasTranslation($optionValueTranslation)) {
                $optionValue->addTranslation($optionValueTranslation);
            }
        }
        if (!$productVariant->hasOptionValue($optionValue)) {
            $productVariant->addOptionValue($optionValue);
        }
    }

    private function handleMetricOption(ProductOptionInterface $productOption, string $optionCode, array $akeneoDataValue, ProductInterface $product, ProductVariantInterface $productVariant): void
    {
        if (!array_key_exists('amount', $akeneoDataValue)) {
            throw new LogicException('Amount key not found');
        }
        $floatAmount = (string) ($akeneoDataValue['amount']);
        if (!array_key_exists('unit', $akeneoDataValue)) {
            throw new LogicException('Unit key not found');
        }
        $unit = (string) $akeneoDataValue['unit'];
        $optionValueCode = $this->createOptionValueCode($optionCode, $floatAmount, $unit);

        $optionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);

        /** @var string[] $locales */
        $locales = $this->getLocaleCodes($product);

        foreach ($locales as $localeCode) {
            $label = $floatAmount . ' ' . $unit;
            if ($this->translator !== null) {
                $label = $this->translator->trans('webgriffe_sylius_akeneo.ui.metric_amount_unit', ['unit' => $unit, 'amount' => $floatAmount], null, $localeCode);
            }
            $optionValue = $this->addOptionValueTranslation($optionValue, $localeCode, $label);
        }
        if (!$productVariant->hasOptionValue($optionValue)) {
            $productVariant->addOptionValue($optionValue);
        }
    }

    private function handleBooleanOption(ProductOptionInterface $productOption, string $optionCode, bool $akeneoDataValue, ProductInterface $product, ProductVariantInterface $productVariant): void
    {
        $optionValueCode = $this->createOptionValueCode($optionCode, (string) $akeneoDataValue);

        $optionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);

        /** @var string[] $locales */
        $locales = $this->getLocaleCodes($product);
        foreach ($locales as $localeCode) {
            $label = (string) $akeneoDataValue;
            if ($this->translator !== null) {
                $label = $akeneoDataValue ? $this->translator->trans('sylius.ui.yes_label', [], null, $localeCode) : $this->translator->trans('sylius.ui.no_label', [], null, $localeCode);
            }
            $optionValue = $this->addOptionValueTranslation($optionValue, $localeCode, $label);
        }
        if (!$productVariant->hasOptionValue($optionValue)) {
            $productVariant->addOptionValue($optionValue);
        }
    }

    private function getProductOption(string $optionCode, ProductVariantInterface $productVariant, ProductInterface $product): ProductOptionInterface
    {
        /** @var ProductOptionInterface|null $productOption */
        $productOption = $this->productOptionRepository->findOneBy(['code' => $optionCode]);
        // TODO productOptionRepository could be removed by getting product option from product with something like:
        //        $productOption = $product->getOptions()->filter(
        //            function (ProductOptionInterface $productOption) use ($optionCode) {
        //                return $productOption->getCode() === $optionCode;
        //            }
        //        )->first();
        if ($productOption === null) {
            throw new RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option "%s" is not set on the parent product "%s".',
                    (string) $productVariant->getCode(),
                    $optionCode,
                    (string) $product->getCode()
                )
            );
        }

        return $productOption;
    }

    private function getOrCreateProductOptionValue(string $optionValueCode, ProductOptionInterface $productOption): ProductOptionValueInterface
    {
        /** @var ProductOptionValueInterface|null $optionValue */
        $optionValue = $this->productOptionValueRepository->findOneBy(['code' => $optionValueCode]);
        if (!$optionValue instanceof ProductOptionValueInterface) {
            /** @var ProductOptionValueInterface $optionValue */
            $optionValue = $this->productOptionValueFactory->createNew();
            $optionValue->setCode($optionValueCode);
            $optionValue->setOption($productOption);
            $productOption->addValue($optionValue);
        }

        return $optionValue;
    }

    private function getLocaleCodes(ProductInterface $product): array
    {
        $locales = [];
        if ($this->translationLocaleProvider !== null) {
            $locales = $this->translationLocaleProvider->getDefinedLocalesCodes();
        } else {
            foreach ($product->getTranslations() as $translation) {
                $locale = $translation->getLocale();
                if ($locale === null) {
                    continue;
                }
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    private function addOptionValueTranslation(
        ProductOptionValueInterface $optionValue,
        string $localeCode,
        string $label
    ): ProductOptionValueInterface {
        $optionValueTranslation = $optionValue->getTranslation($localeCode);
        if ($optionValueTranslation->getLocale() !== $localeCode) {
            /** @var ProductOptionValueTranslationInterface $optionValueTranslation */
            $optionValueTranslation = $this->productOptionValueTranslationFactory->createNew();
            $optionValueTranslation->setLocale($localeCode);
        }
        $optionValueTranslation->setValue($label);
        if (!$optionValue->hasTranslation($optionValueTranslation)) {
            $optionValue->addTranslation($optionValueTranslation);
        }

        return $optionValue;
    }

    private function createOptionValueCode(string ...$pieces): string
    {
        $slugifiedPieces = array_map(static function (string $word): string {
            return str_replace(['.', ','], '', $word);
        }, $pieces);

        return implode('_', $slugifiedPieces);
    }
}
