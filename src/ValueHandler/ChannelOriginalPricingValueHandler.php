<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ChannelOriginalPricingValueHandler extends AbstractChannelPricingValueHandler
{
    protected function setPrice(ChannelPricingInterface $channelPricing, int $price): void
    {
        $channelPricing->setOriginalPrice($price);
    }

    protected function setPrices(ProductVariantInterface $variant, array $currenciesPrices): void
    {
        if (empty($currenciesPrices)) {
            $this->unsetAll($variant);

        } else {
            parent::setPrices($variant, $currenciesPrices);
        }
    }

    private function unsetAll(ProductVariantInterface $variant): void
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        foreach ($channels as $channel) {
            $channelPricing = $variant->getChannelPricingForChannel($channel);

            if ($channelPricing) {
                $channelPricing->setOriginalPrice(null);
            }
        }
    }
}
