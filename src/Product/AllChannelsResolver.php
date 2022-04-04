<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Webmozart\Assert\Assert;

final class AllChannelsResolver implements ChannelsResolverInterface
{
    public function __construct(private ChannelRepositoryInterface $channelRepository)
    {
    }

    /**
     * @inheritdoc
     */
    public function resolve(array $akeneoProduct): array
    {
        $enabledChannels = $this->channelRepository->findBy(['enabled' => true]);
        Assert::allIsInstanceOf($enabledChannels, ChannelInterface::class);

        return $enabledChannels;
    }
}
