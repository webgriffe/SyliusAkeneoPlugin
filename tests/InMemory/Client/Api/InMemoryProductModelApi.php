<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ProductModel;

final class InMemoryProductModelApi extends InMemoryApi implements ProductModelApiInterface
{
    protected function getResourceClass(): string
    {
        return ProductModel::class;
    }
}
