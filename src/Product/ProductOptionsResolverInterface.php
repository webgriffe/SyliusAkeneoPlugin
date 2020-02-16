<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Product\Model\ProductOptionInterface;

interface ProductOptionsResolverInterface
{
    /** @return ProductOptionInterface[] */
    public function resolve(array $akeneoProduct): array;
}
