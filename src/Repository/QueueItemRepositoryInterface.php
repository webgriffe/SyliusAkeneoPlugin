<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Repository;

use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;

interface QueueItemRepositoryInterface
{
    /**
     * @return array|QueueItemInterface[]
     */
    public function findAllToImport(): array;
}