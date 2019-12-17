<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM;

use Doctrine\ORM\EntityRepository;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

class QueueItemRepository extends EntityRepository implements QueueItemRepositoryInterface
{
}