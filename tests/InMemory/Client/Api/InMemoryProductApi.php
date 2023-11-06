<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;

final class InMemoryProductApi extends InMemoryApi implements ProductApiInterface
{

    protected function getResourceClass(): string
    {
        return Product::class;
    }
}
