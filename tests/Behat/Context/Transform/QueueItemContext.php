<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Transform;

use Behat\Behat\Context\Context;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class QueueItemContext implements Context
{
    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    public function __construct(QueueItemRepositoryInterface $queueItemRepository)
    {
        $this->queueItemRepository = $queueItemRepository;
    }

    /**
     * @Transform /^"([^"]+)" queue item$/
     */
    public function getQueueItemByIdentifier(string $akeneoIdentifier): QueueItemInterface
    {
        $queueItems = $this->queueItemRepository->findBy(['akeneoIdentifier' => $akeneoIdentifier]);

        Assert::count(
            $queueItems,
            1,
            sprintf('%d queue items has been found with identifier "%s".', count($queueItems), $akeneoIdentifier)
        );

        return $queueItems[0];
    }
}
