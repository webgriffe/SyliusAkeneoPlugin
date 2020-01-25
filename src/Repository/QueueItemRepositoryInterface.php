<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Repository;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;

interface QueueItemRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array|QueueItemInterface[]
     */
    public function findAllToImport(): array;

    public function findOneToImport(string $akeneoEntity, string $akeneoIdentifier): ?QueueItemInterface;
}
