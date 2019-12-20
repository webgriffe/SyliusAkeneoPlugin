<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;

interface ValueHandlerResolverInterface
{
    public function resolve(ProductInterface $product, string $attribute, array $value): ?ValueHandlerInterface;
}
