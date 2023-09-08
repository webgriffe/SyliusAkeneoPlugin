<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM;

use DateTimeInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResultInterface;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;
use Webmozart\Assert\Assert;

final class ItemImportResultRepository extends EntityRepository implements ItemImportResultRepositoryInterface
{
    public function findToCleanup(DateTimeInterface $dateLimit): array
    {
        $result = $this->createQueryBuilder('i')
            ->where('i.createdAt IS NOT NULL')
            ->andWhere('i.createdAt <= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->getResult()
        ;
        Assert::isArray($result);

        return $result;
    }
}
