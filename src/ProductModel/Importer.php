<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
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

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory,
        CategoriesHandlerInterface $categoriesHandler,
        FamilyVariantHandlerInterface $familyVariantHandler,
        ValueHandlersRegistryInterface $valueHandlersRegistry
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoriesHandler = $categoriesHandler;
        $this->familyVariantHandler = $familyVariantHandler;
        $this->valueHandlersRegistry = $valueHandlersRegistry;
    }

    public function import(string $identifier): void
    {
        // TODO API call to get product model by $identifier
        /** @var array $productModelResponse */
        $productModelResponse = json_decode(file_get_contents(__DIR__ . '/../../product-model.json'), true);
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

        $family = $productModelResponse['family'];
        $familyVariant = $productModelResponse['family_variant'];
        // TODO API call to get family variant by $family and $familyVariant
        $familyVariantResponse = json_decode(file_get_contents(__DIR__ . '/../../family-variant.json'), true);

        $this->familyVariantHandler->handle($product, $familyVariantResponse);

        $this->productRepository->add($product);
    }
}
