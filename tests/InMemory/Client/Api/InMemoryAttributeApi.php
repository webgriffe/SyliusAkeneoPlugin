<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;

final class InMemoryAttributeApi extends InMemoryApi implements AttributeApiInterface
{
    protected function getResourceClass(): string
    {
        return Attribute::class;
    }
}
