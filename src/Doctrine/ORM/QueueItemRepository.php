<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

class QueueItemRepository extends EntityRepository implements QueueItemRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findAllToImport(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.importedAt IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }
}