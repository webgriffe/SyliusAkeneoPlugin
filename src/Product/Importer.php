<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

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
        if (count($product->getOptions()) !== 1) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the parent product "%s" has an invalid number of options. ' .
                    'This importer only supports single option products but this product has "%d" options.',
                    $identifier,
                    $parentCode,
                    count($product->getOptions())
                )
            );
        }
        $optionCode = $product->getOptions()[0]->getCode();
        if (!array_key_exists($optionCode, $productVariantResponse['values']) ||
            count($productVariantResponse['values'][$optionCode]) === 0
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s". ' .
                    'But no value for this attribute is set on Akeneo.',
                    $identifier,
                    $parentCode,
                    $optionCode
                )
            );
        }
        if (count($productVariantResponse['values'][$optionCode]) > 1) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s". ' .
                    'More than one value is set for this attribute on Akeneo but this importer only supports single ' .
                    'value for product options.',
                    $identifier,
                    $parentCode,
                    $optionCode
                )
            );
        }
        $partialValueCode = $productVariantResponse['values'][$optionCode][0]['data'];
        $fullValueCode = $optionCode . '_' . $partialValueCode;
        // TODO Try to check if option value already exists
        $akeneoAttributeOption = $this->apiClient->findAttributeOption($optionCode, $partialValueCode);
        if (!$akeneoAttributeOption) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is "%s". ' .
                    'The option value for this variant is "%s" but there is no such option on Akeneo.',
                    $identifier,
                    $parentCode,
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
                    $identifier,
                    $parentCode,
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
        foreach ($akeneoAttributeOption['labels'] as $localeCode => $value) {
            /** @var ProductOptionValueTranslationInterface $optionValueTranslation */
            $optionValueTranslation = $this->productOptionValueTranslationFactory->createNew();
            $optionValueTranslation->setLocale($localeCode);
            $optionValueTranslation->setValue($value);
            $optionValue->addTranslation($optionValueTranslation);
            $this->productOptionValueRepository->add($optionValue);
        }

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantFactory->createNew();
        $productVariant->setProduct($product);
        $productVariant->setCode($identifier);
        $productVariant->addOptionValue($optionValue);
        $this->productVariantRepository->add($productVariant);
    }
}
