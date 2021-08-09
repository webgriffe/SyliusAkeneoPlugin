<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ChannelPricingInterface;

final class ChannelPricingValueHandler extends AbstractChannelPricingValueHandler
{
    protected function setPrice(ChannelPricingInterface $channelPricing, int $price): void
    {
        $channelPricing->setPrice($price);
    }
}
