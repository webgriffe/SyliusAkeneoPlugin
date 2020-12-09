<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM;

use DateTime;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
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

    public function findOneToImport(string $akeneoEntity, string $akeneoIdentifier): ?QueueItemInterface
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.importedAt IS NULL')
            ->andWhere('o.akeneoEntity = :akeneoEntity')
            ->andWhere('o.akeneoIdentifier = :akeneoIdentifier')
            ->setParameter('akeneoEntity', $akeneoEntity)
            ->setParameter('akeneoIdentifier', $akeneoIdentifier)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findToCleanup(DateTime $dateLimit): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.importedAt IS NOT NULL')
            ->andWhere('o.createdAt <= :dateLimit')
            ->setParameter('dateLimit', $dateLimit->format('Y-m-d'))
            ->getQuery()
            ->getResult()
        ;
    }
}
