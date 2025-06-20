<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

final class VariantStatusResolver implements StatusResolverInterface
{
    #[\Override]
    public function resolve(array $akeneoProduct): bool
    {
        return $akeneoProduct['enabled'];
    }
}
