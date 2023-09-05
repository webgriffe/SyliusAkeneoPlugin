<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;

final class ItemImportResultRepository extends EntityRepository implements ItemImportResultRepositoryInterface
{
}
