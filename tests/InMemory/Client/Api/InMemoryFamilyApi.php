<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Family;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ResourceInterface;
use Webmozart\Assert\Assert;

final class InMemoryFamilyApi extends InMemoryApi implements FamilyApiInterface
{
    /** @var array<string, Family> */
    public static array $families = [];

    protected function getResourceClass(): string
    {
        return Family::class;
    }

    public static function clear(): void
    {
        self::$families = [];
    }

    public function getResources(): array
    {
        return self::$families;
    }

    public static function addResource(ResourceInterface $resource): void
    {
        Assert::isInstanceOf($resource, Family::class);
        self::$families[$resource->code] = $resource;
    }
}
