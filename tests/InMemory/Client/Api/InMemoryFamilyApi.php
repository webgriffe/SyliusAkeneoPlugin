<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Family;

final class InMemoryFamilyApi extends InMemoryApi implements FamilyApiInterface
{
    protected function getResourceClass(): string
    {
        return Family::class;
    }
}
