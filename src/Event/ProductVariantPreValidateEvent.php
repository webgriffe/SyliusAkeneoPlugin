<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Event;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Dispatched by the Product importer just before the imported product variant (and its product) is validated.
 * It allows to change the product variant or its product data, for example to set data that is not handled on Akeneo.
 */
final class ProductVariantPreValidateEvent
{
    public function __construct(
        private ProductVariantInterface $productVariant,
        private ProductInterface $product,
        private array $akeneoProduct,
    ) {
    }

    public function getProductVariant(): ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function getAkeneoProduct(): array
    {
        return $this->akeneoProduct;
    }
}
