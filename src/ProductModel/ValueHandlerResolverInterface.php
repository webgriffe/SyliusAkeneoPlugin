<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;

interface ValueHandlerResolverInterface
{
    /**
     * @param ProductInterface $product
     * @param string $attribute
     * @param array $value
     * @return ValueHandlerInterface|null
     */
    public function resolve(ProductInterface $product, string $attribute, array $value): ?ValueHandlerInterface;
}
