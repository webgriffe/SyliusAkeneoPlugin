<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;

final class FamilyVariantApiMock implements FamilyVariantApiInterface
{
    public function get($familyCode, $familyVariantCode): array
    {
        return ApiClientMock::jsonFileOrHttpNotFoundException(
            __DIR__ . '/../DataFixtures/ApiClientMock/FamilyVariant/' . $familyCode . '/' . $familyVariantCode . '.json'
        );
    }

    public function create($familyCode, $familyVariantCode, array $data = []): int
    {
        // TODO: Implement create() method.
    }

    public function upsert($familyCode, $familyVariantCode, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function listPerPage(
        $familyCode,
        $limit = 10,
        $withCount = false,
        array $queryParameters = []
    ): PageInterface {
        // TODO: Implement listPerPage() method.
    }

    public function all($familyCode, $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        // TODO: Implement all() method.
    }

    public function upsertList($familyCode, $familyVariants): \Traversable
    {
        // TODO: Implement upsertList() method.
    }
}
