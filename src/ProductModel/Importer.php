<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ProductFactoryInterface
     */
    private $productFactory;
    /**
     * @var CategoriesHandlerInterface
     */
    private $categoriesHandler;
    /**
     * @var FamilyVariantHandlerInterface
     */
    private $familyVariantHandler;
    /**
     * @var ValueHandlersRegistryInterface
     */
    private $valueHandlersRegistry;
    /**
     * @var ApiClientInterface
     */
    private $apiClient;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory,
        CategoriesHandlerInterface $categoriesHandler,
        FamilyVariantHandlerInterface $familyVariantHandler,
        ValueHandlersRegistryInterface $valueHandlersRegistry,
        ApiClientInterface $apiClient
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoriesHandler = $categoriesHandler;
        $this->familyVariantHandler = $familyVariantHandler;
        $this->valueHandlersRegistry = $valueHandlersRegistry;
        $this->apiClient = $apiClient;
    }

    public function import(string $identifier): void
    {
        /** @var array $productModelResponse */
        $productModelResponse = $this->apiClient->findProductModel($identifier);
        $code = $productModelResponse['code'];
        $product = $this->productRepository->findOneByCode($code);
        if (!$product) {
            $product = $this->productFactory->createNew();
        }
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductInterface $product */
        $this->categoriesHandler->handle($product, $productModelResponse['categories']);

        foreach ($productModelResponse['values'] as $attribute => $value) {
            foreach ($this->valueHandlersRegistry->all() as $valueHandler) {
                if (!$valueHandler->supports($product, $attribute, $value)) {
                    continue;
                }
                $valueHandler->handle($product, $attribute, $value);
                continue 2;
            }
            // TODO no value handler for this attribute. Throw? Log?
        }

        $familyCode = $productModelResponse['family'];
        $familyVariantCode = $productModelResponse['family_variant'];
        $familyVariantResponse = $this->apiClient->findFamilyVariant($familyCode, $familyVariantCode);

        $this->familyVariantHandler->handle($product, $familyVariantResponse);

        $this->productRepository->add($product);
    }
}
