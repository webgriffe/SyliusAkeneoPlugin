<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ProductModel;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ResourceInterface;
use Webmozart\Assert\Assert;

final class InMemoryProductModelApi extends InMemoryApi implements ProductModelApiInterface
{
    /** @var array<string, ProductModel> */
    public static array $productModels = [];

    protected function getResourceClass(): string
    {
        return ProductModel::class;
    }

    public function getResources(): array
    {
        return self::$productModels;
    }

    public static function addResource(ResourceInterface $resource): void
    {
        Assert::isInstanceOf($resource, ProductModel::class);
        self::$productModels[$resource->code] = $resource;
    }
}
