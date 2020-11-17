<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ProductOptionValueHandler implements ValueHandlerInterface
{
    /** @var AkeneoPimClientInterface */
    private $apiClient;

    /** @var ProductOptionRepositoryInterface */
    private $productOptionRepository;

    /** @var FactoryInterface */
    private $productOptionValueFactory;

    /** @var FactoryInterface */
    private $productOptionValueTranslationFactory;

    /** @var RepositoryInterface */
    private $productOptionValueRepository;

    public function __construct(
        AkeneoPimClientInterface $apiClient,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        RepositoryInterface $productOptionValueRepository
    ) {
        $this->apiClient = $apiClient;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productOptionValueTranslationFactory = $productOptionValueTranslationFactory;
        $this->productOptionValueRepository = $productOptionValueRepository;
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
            throw new \InvalidArgumentException(
                sprintf(
                    'This option value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($productVariant) ? get_class($productVariant) : gettype($productVariant)
                )
            );
        }
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        if (count($akeneoValue) > 1) {
            throw new \RuntimeException(
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
        $partialValueCode = $akeneoValue[0]['data'];
        $fullValueCode = $optionCode . '_' . $partialValueCode;

        try {
            $akeneoAttributeOption = $this->apiClient->getAttributeOptionApi()->get($optionCode, $partialValueCode);
        } catch (HttpException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 404) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                        '"%s". The option value for this variant is "%s" but there is no such option on Akeneo.',
                        $productVariant->getCode(),
                        $product->getCode(),
                        $optionCode,
                        $partialValueCode
                    )
                );
            }

            throw $e;
        }

        $productOption = $this->productOptionRepository->findOneBy(['code' => $optionCode]);
        // TODO productOptionRepository could be removed by getting product option from product with something like:
        //        $productOption = $product->getOptions()->filter(
        //            function (ProductOptionInterface $productOption) use ($optionCode) {
        //                return $productOption->getCode() === $optionCode;
        //            }
        //        )->first();
        if (!$productOption) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option "%s" is not set on the parent product "%s".',
                    $productVariant->getCode(),
                    $optionCode,
                    $product->getCode()
                )
            );
        }
        Assert::isInstanceOf($productOption, ProductOptionInterface::class);
        /** @var ProductOptionInterface $productOption */
        $optionValue = $this->productOptionValueRepository->findOneBy(['code' => $fullValueCode]);
        if (!$optionValue instanceof ProductOptionValueInterface) {
            /** @var ProductOptionValueInterface $optionValue */
            $optionValue = $this->productOptionValueFactory->createNew();
            $optionValue->setCode($fullValueCode);
            $optionValue->setOption($productOption);
            $productOption->addValue($optionValue);
        }
        foreach ($akeneoAttributeOption['labels'] as $localeCode => $label) {
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
        $this->productOptionValueRepository->add($optionValue);
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
}
