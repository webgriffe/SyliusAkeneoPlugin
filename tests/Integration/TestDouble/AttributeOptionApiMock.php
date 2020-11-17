<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;

final class AttributeOptionApiMock implements AttributeOptionApiInterface
{
    public function get($attributeCode, $code): array
    {
        return ApiClientMock::jsonFileOrHttpNotFoundException(
            __DIR__ . '/../DataFixtures/ApiClientMock/AttributeOption/' . $attributeCode . '/' . $code . '.json'
        );
    }

    public function listPerPage(
        $attributeCode,
        $limit = 10,
        $withCount = false,
        array $queryParameters = []
    ): PageInterface {
        // TODO: Implement listPerPage() method.
    }

    public function all($attributeCode, $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        // TODO: Implement all() method.
    }

    public function create($attributeCode, $attributeOptionCode, array $data = []): int
    {
        // TODO: Implement create() method.
    }

    public function upsert($attributeCode, $attributeOptionCode, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertList($attributeCode, $attributeOptions): \Traversable
    {
        // TODO: Implement upsertList() method.
    }
}
