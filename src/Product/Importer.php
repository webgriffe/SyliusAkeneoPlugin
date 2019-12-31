<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

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

    public function __construct(
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        ApiClientInterface $apiClient
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
    }

    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }
        $parentCode = $productVariantResponse['parent'];
        // TODO Handle $parentCode=null so the importer should also create the related Product
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
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantFactory->createNew();
        $productVariant->setProduct($product);
        $productVariant->setCode($identifier);
        $this->productVariantRepository->add($productVariant);
    }
}
