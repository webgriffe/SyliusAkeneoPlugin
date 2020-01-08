<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerResolverInterface;

final class Importer implements ImporterInterface
{
    /** @var ProductVariantFactoryInterface */
    private $productVariantFactory;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ValueHandlerResolverInterface */
    private $variantValueHandlerResolver;

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ApiClientInterface $apiClient,
        ValueHandlerResolverInterface $variantValueHandlerResolver
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
        $this->variantValueHandlerResolver = $variantValueHandlerResolver;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        $productVariant = $this->productVariantRepository->findOneBy(['code' => $identifier]);
        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($identifier);
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
        $productVariant->setProduct($product);

        foreach ($productVariantResponse['values'] as $attribute => $value) {
            $valueHandler = $this->variantValueHandlerResolver->resolve($productVariant, $attribute, $value);
            if ($valueHandler === null) {
                // TODO no value handler for this attribute. Throw? Log?
                // throw new \RuntimeException(sprintf('No ValueHandler found for attribute "%s"', $attribute));
                continue;
            }
            $valueHandler->handle($productVariant, $attribute, $value);
        }

        $this->productVariantRepository->add($productVariant);
    }
}
