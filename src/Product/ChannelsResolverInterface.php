<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ChannelInterface;

interface ChannelsResolverInterface
{
    /** @return ChannelInterface[] */
    public function resolve(array $akeneoProduct): array;
}
