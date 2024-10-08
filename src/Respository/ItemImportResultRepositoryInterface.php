<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Respository;

if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}
use DateTimeInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResultInterface;

/**
 * @extends RepositoryInterface<ItemImportResultInterface>
 */
interface ItemImportResultRepositoryInterface extends RepositoryInterface
{
    public function findToCleanup(DateTimeInterface $dateLimit): array;
}
