<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ResourceInterface;
use Webmozart\Assert\Assert;

final class InMemoryProductApi extends InMemoryApi implements ProductApiInterface
{
    /**
     * @var array<string, Product>
     */
    public static array $products = [];

    public function getResources(): array
    {
        return self::$products;
    }

    protected function getResourceClass(): string
    {
        return Product::class;
    }

    public static function addResource(ResourceInterface $resource): void
    {
        Assert::isInstanceOf($resource, Product::class);
        self::$products[$resource->getIdentifier()] = $resource;
    }
}
