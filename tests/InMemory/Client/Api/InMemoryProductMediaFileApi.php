<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DataFixtures\DataFixture;

final class InMemoryProductMediaFileApi implements MediaFileApiInterface
{
    public function download(string $code): ResponseInterface
    {
        $path = DataFixture::path . '/Media/' . $code;
        if (!file_exists($path)) {
            throw new HttpException("File '$path' does not exists.", new Request('GET', '/'), new Response(404));
        }

        return new Response(200, [], file_get_contents($path));
    }

    public function get(string $code): array
    {
        // TODO: Implement get() method.
    }

    public function listPerPage(int $limit = 100, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 100, array $queryParameters = []): ResourceCursorInterface
    {
        // TODO: Implement all() method.
    }

    public function create($mediaFile, array $data): string
    {
        // TODO: Implement create() method.
    }
}
