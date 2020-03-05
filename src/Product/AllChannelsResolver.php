<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;

final class AllChannelsResolver implements ChannelsResolverInterface
{
    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    public function __construct(ChannelRepositoryInterface $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $akeneoProduct): array
    {
        return $this->channelRepository->findBy(['enabled' => true]);
    }
}
