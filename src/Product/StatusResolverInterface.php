<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

interface StatusResolverInterface
{
    public function resolve(array $akeneoProduct): bool;
}
