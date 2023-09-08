<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Respository;

use DateTimeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResultInterface;

/**
 * @extends RepositoryInterface<ItemImportResultInterface>
 */
interface ItemImportResultRepositoryInterface extends RepositoryInterface
{
    public function findToCleanup(DateTimeInterface $dateLimit): array;
}
