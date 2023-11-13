<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Promise\Promise;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\FamilyVariant;

final class InMemoryFamilyVariantApi implements FamilyVariantApiInterface
{
    /** @var array<string, <string, FamilyVariant>> */
    public static array $familyVariants = [];

    public static function addResource(string $familyCode, FamilyVariant $familyVariant): void
    {
        self::$familyVariants[$familyCode][$familyVariant->code] = $familyVariant;
    }

    public function get($familyCode, $familyVariantCode): array
    {
        if (!array_key_exists($familyCode, self::$familyVariants)) {
            throw $this->createNotFoundException();
        }
        $familyVariants = self::$familyVariants[$familyCode];
        if (!array_key_exists($familyVariantCode, $familyVariants)) {
            throw $this->createNotFoundException();
        }

        return $familyVariants[$familyVariantCode]->__serialize();
    }

    public function create($familyCode, $familyVariantCode, array $data = []): int
    {
        // TODO: Implement create() method.
    }

    public function upsert($familyCode, $familyVariantCode, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertAsync($familyCode, $familyVariantCode, array $data = []): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsync() method.
    }

    public function listPerPage($familyCode, $limit = 100, $withCount = false, array $queryParameters = []): PageInterface
    {
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

    public function upsertAsyncList($familyCode, $familyVariants): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsyncList() method.
    }

    private function createNotFoundException(): NotFoundHttpException
    {
        return new NotFoundHttpException('Attribute option not found', new Request('GET', '/'), new Response(404));
    }
}
