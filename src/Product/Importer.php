<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    /** @var ProductVariantFactoryInterface */
    private $productVariantFactory;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductOptionRepositoryInterface */
    private $productOptionRepository;

    /** @var FactoryInterface */
    private $productOptionValueFactory;

    /** @var FactoryInterface */
    private $productOptionValueTranslationFactory;

    /** @var RepositoryInterface */
    private $productOptionValueRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        RepositoryInterface $productOptionValueRepository,
        ApiClientInterface $apiClient
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productOptionValueTranslationFactory = $productOptionValueTranslationFactory;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->apiClient = $apiClient;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantFactory->createNew();
        $productVariant->setCode($identifier);

        $parentCode = $productVariantResponse['parent'];
        // TODO Handle $parentCode=null (it happens when Product doesn't belong to a ProductModel) so the importer
        //      should also create the related Sylius Product
        $product = $this->productRepository->findOneByCode($parentCode);
        if (!$product) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the parent product "%s" does not exists on Sylius.',
                    $identifier,
                    $parentCode
                )
            );
        }
        $productVariant->setProduct($product);

        /**
         * @var string
         * @var array $value
         */
        foreach ($productVariantResponse['values'] as $attribute => $value) {
            if ($this->isVariantOption($productVariant, $attribute)) {
                $optionValue = $this->getOrCreateProductOptionValue($productVariant, $attribute, $value);

                $productVariant->addOptionValue($optionValue);
                $this->productOptionValueRepository->add($optionValue);
            }
        }

        $this->productVariantRepository->add($productVariant);
    }

    private function isVariantOption(ProductVariantInterface $productVariant, string $attribute): bool
    {
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        foreach ($product->getOptions() as $option) {
            if ($attribute === $option->getCode()) {
                return true;
            }
        }

        return false;
    }

    private function getOrCreateProductOptionValue(
        ProductVariantInterface $productVariant,
        string $optionCode,
        array $value
    ): ProductOptionValueInterface {
        $product = $productVariant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        if (count($value) > 1) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s". ' .
                    'More than one value is set for this attribute on Akeneo but this importer only supports single ' .
                    'value for product options.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode
                )
            );
        }
        $partialValueCode = $value[0]['data'];
        $fullValueCode = $optionCode . '_' . $partialValueCode;
        // TODO Try to check if option value already exists
        $akeneoAttributeOption = $this->apiClient->findAttributeOption($optionCode, $partialValueCode);
        if (!$akeneoAttributeOption) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s". ' .
                    'The option value for this variant is "%s" but there is no such option on Akeneo.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode,
                    $partialValueCode
                )
            );
        }
        $productOption = $this->productOptionRepository->findOneBy(['code' => $optionCode]);
        if (!$productOption) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s" but this ' .
                    'doesn\'t exist on Sylius and it should.',
                    $productVariant->getCode(),
                    $product->getCode(),
                    $optionCode
                )
            );
        }
        Assert::isInstanceOf($productOption, ProductOptionInterface::class);
        /** @var ProductOptionInterface $productOption */
        /** @var ProductOptionValueInterface $optionValue */
        $optionValue = $this->productOptionValueFactory->createNew();
        $optionValue->setCode($fullValueCode);
        $optionValue->setOption($productOption);
        foreach ($akeneoAttributeOption['labels'] as $localeCode => $label) {
            /** @var ProductOptionValueTranslationInterface $optionValueTranslation */
            $optionValueTranslation = $this->productOptionValueTranslationFactory->createNew();
            $optionValueTranslation->setLocale($localeCode);
            $optionValueTranslation->setValue($label);
            $optionValue->addTranslation($optionValueTranslation);
        }

        return $optionValue;
    }
}
