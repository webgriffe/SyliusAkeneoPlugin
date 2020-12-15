<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Repository;

use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CleanableQueueItemRepositoryInterface extends RepositoryInterface
{
    public function findToCleanup(\DateTime $dateLimit): array;
}
