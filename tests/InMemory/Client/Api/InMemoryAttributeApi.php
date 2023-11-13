<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ResourceInterface;
use Webmozart\Assert\Assert;

final class InMemoryAttributeApi extends InMemoryApi implements AttributeApiInterface
{
    /**
     * @var array<string, Attribute>
     */
    public static array $attributes = [];

    protected function getResourceClass(): string
    {
        return Attribute::class;
    }

    public function getResources(): array
    {
        return self::$attributes;
    }

    public static function addResource(ResourceInterface $resource): void
    {
        Assert::isInstanceOf($resource, Attribute::class);
        self::$attributes[$resource->code] = $resource;
    }
}
