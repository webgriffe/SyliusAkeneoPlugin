<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

final class StatusResolver implements StatusResolverInterface
{
    #[\Override]
    public function resolve(array $akeneoProduct): bool
    {
        if ($akeneoProduct['parent'] !== null) {
            return true;
        }

        return (bool) $akeneoProduct['enabled'];
    }
}
