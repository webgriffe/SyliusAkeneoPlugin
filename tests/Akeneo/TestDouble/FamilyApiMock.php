<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Akeneo\TestDouble;

use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Http\Promise\Promise;
use Psr\Http\Message\StreamInterface;

final class FamilyApiMock implements FamilyApiInterface
{
    public function create(string $code, array $data = []): int
    {
        // TODO: Implement create() method.
    }

    public function get(string $code): array
    {
        return $this->jsonDecodeOrNull(__DIR__ . '/../Data/Family/' . $code . '.json');
    }

    public function listPerPage(int $limit = 10, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        // TODO: Implement all() method.
    }

    public function upsert(string $code, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertAsync(string $code, array $data = []): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsync() method.
    }

    public function upsertList($resources): \Traversable
    {
        // TODO: Implement upsertList() method.
    }

    public function upsertAsyncList(StreamInterface|array $resources): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsyncList() method.
    }

    /**
     * @return mixed|null
     */
    private function jsonDecodeOrNull(string $filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true, 512, \JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
