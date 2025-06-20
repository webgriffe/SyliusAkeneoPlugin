<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

if (!interface_exists(\Sylius\Resource\Factory\FactoryInterface::class)) {
    class_alias(\Sylius\Resource\Factory\FactoryInterface::class, \Sylius\Component\Resource\Factory\FactoryInterface::class);
}
if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionValueHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoAttributeOption array{_links: array, code: string, attribute: string, sort_order: int, labels: array<string, ?string>}
 */
final class ProductOptionValueHandler implements ValueHandlerInterface
{
    use ProductOptionValueHelperTrait;

    /**
     * @param FactoryInterface<ProductOptionValueInterface> $productOptionValueFactory
     * @param FactoryInterface<ProductOptionValueTranslationInterface> $productOptionValueTranslationFactory
     * @param RepositoryInterface<ProductOptionValueInterface> $productOptionValueRepository
     */
    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private FactoryInterface $productOptionValueFactory,
        private FactoryInterface $productOptionValueTranslationFactory,
        private RepositoryInterface $productOptionValueRepository,
        private TranslationLocaleProviderInterface $translationLocaleProvider,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param mixed $subject
     */
    #[\Override]
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $this->isVariantOption($subject, $attribute);
    }

    /**
     * @param mixed $productVariant
     */
    #[\Override]
    public function handle($productVariant, string $optionCode, array $akeneoValue): void
    {
        if (!$productVariant instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'This option value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($productVariant),
                ),
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
                    $optionCode,
                ),
            );
        }

        try {
            /** @var AkeneoAttribute $akeneoAttribute */
            $akeneoAttribute = $this->apiClient->getAttributeApi()->get($optionCode);
        } catch (HttpException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 404) {
                throw new RuntimeException(
                    sprintf(
                        'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                        '"%s". The attribute "%s" does not exists.',
                        $productVariant->getCode(),
                        $product->getCode(),
                        $optionCode,
                        $optionCode,
                    ),
                );
            }

            throw $e;
        }
        /** @var string|array|bool|int $akeneoValueData */
        $akeneoValueData = $akeneoValue[0]['data'];

        $productVariant->getOptionValues()->clear();

        $productOption = $this->getProductOption($optionCode, $productVariant, $product);

        /**
         * See https://help.akeneo.com/serenity-build-your-catalog/30-serenity-manage-your-families-and-variant-families?from_search=132874388#manage-family-variants:~:text=An%20attribute%20of%20the%20family%20could%20be%20a%C2%A0variant%20axis%C2%A0only%20if%20its%20attribute%20type%20is%20one%20of%20the%20following%3A
         */
        $attributeType = $akeneoAttribute['type'];
        switch ($attributeType) {
            case 'pim_catalog_simpleselect':
                Assert::string($akeneoValueData);
                $this->handleSelectOption($productOption, $optionCode, $akeneoValueData, $product, $productVariant);

                break;
            case 'pim_catalog_metric':
                Assert::isArray($akeneoValueData);
                $this->handleMetricOption($productOption, $optionCode, $akeneoValueData, $productVariant);

                break;
            case 'pim_catalog_boolean':
                Assert::boolean($akeneoValueData);
                $this->handleBooleanOption($productOption, $optionCode, $akeneoValueData, $productVariant);

                break;
            default:
                throw new LogicException(sprintf('The Akeneo attribute type "%s" is not supported from the "%s"', $attributeType, self::class));
        }
    }

    private function handleSelectOption(ProductOptionInterface $productOption, string $optionCode, string $akeneoValue, ProductInterface $product, ProductVariantInterface $productVariant): void
    {
        $optionValueCode = $this->getSyliusProductOptionValueCode($optionCode, $akeneoValue);

        $optionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);

        try {
            /** @var AkeneoAttributeOption $akeneoAttributeOption */
            $akeneoAttributeOption = $this->apiClient->getAttributeOptionApi()->get($optionCode, $akeneoValue);
        } catch (HttpException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 404) {
                throw new RuntimeException(
                    sprintf(
                        'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                        '"%s". The option value for this variant is "%s" but there is no such option on Akeneo.',
                        $productVariant->getCode(),
                        $product->getCode(),
                        $optionCode,
                        $akeneoValue,
                    ),
                );
            }

            throw $e;
        }

        $this->importSelectProductOptionValueTranslations($akeneoAttributeOption, $optionValue);
        if (!$productVariant->hasOptionValue($optionValue)) {
            $productVariant->addOptionValue($optionValue);
        }
    }

    private function handleMetricOption(ProductOptionInterface $productOption, string $optionCode, array $akeneoDataValue, ProductVariantInterface $productVariant): void
    {
        if (!array_key_exists('amount', $akeneoDataValue)) {
            throw new LogicException('Amount key not found');
        }
        $floatAmount = (string) ($akeneoDataValue['amount']);
        if (!array_key_exists('unit', $akeneoDataValue)) {
            throw new LogicException('Unit key not found');
        }
        $unit = (string) $akeneoDataValue['unit'];
        $optionValueCode = $this->getSyliusProductOptionValueCode($optionCode, $floatAmount, $unit);

        $optionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);

        foreach ($this->getDefinedLocaleCodes() as $localeCode) {
            $label = $this->translator->trans('webgriffe_sylius_akeneo.ui.metric_amount_unit', ['unit' => $unit, 'amount' => $floatAmount], null, $localeCode);
            $optionValue = $this->addOrUpdateProductOptionValueTranslation($optionValue, $localeCode, $label);
        }
        if (!$productVariant->hasOptionValue($optionValue)) {
            $productVariant->addOptionValue($optionValue);
        }
    }

    private function handleBooleanOption(ProductOptionInterface $productOption, string $optionCode, bool $akeneoDataValue, ProductVariantInterface $productVariant): void
    {
        $optionValueCode = $this->getSyliusProductOptionValueCode($optionCode, (string) $akeneoDataValue);

        $productOptionValue = $this->getOrCreateProductOptionValue($optionValueCode, $productOption);
        $this->addBooleanProductOptionValueTranslations($akeneoDataValue, $productOptionValue);
        if (!$productVariant->hasOptionValue($productOptionValue)) {
            $productVariant->addOptionValue($productOptionValue);
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
                    (string) $product->getCode(),
                ),
            );
        }

        return $productOption;
    }

    private function getOrCreateProductOptionValue(string $optionValueCode, ProductOptionInterface $productOption): ProductOptionValueInterface
    {
        $optionValue = $this->productOptionValueRepository->findOneBy(['code' => $optionValueCode]);
        if (!$optionValue instanceof ProductOptionValueInterface) {
            $optionValue = $this->createNewProductOptionValue($optionValueCode, $productOption);
        }

        return $optionValue;
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

    #[\Override]
    private function getDefinedLocaleCodes(): array
    {
        return $this->translationLocaleProvider->getDefinedLocalesCodes();
    }

    /**
     * @return FactoryInterface<ProductOptionValueTranslationInterface>
     */
    #[\Override]
    private function getProductOptionValueTranslationFactory(): FactoryInterface
    {
        return $this->productOptionValueTranslationFactory;
    }

    /**
     * @return FactoryInterface<ProductOptionValueInterface>
     */
    #[\Override]
    private function getProductOptionValueFactory(): FactoryInterface
    {
        return $this->productOptionValueFactory;
    }

    #[\Override]
    private function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
