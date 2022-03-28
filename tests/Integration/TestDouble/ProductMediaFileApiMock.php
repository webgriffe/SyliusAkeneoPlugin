<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class ProductMediaFileApiMock implements MediaFileApiInterface
{
    public function download(string $code): ResponseInterface
    {
        // $code should be like 4/a/f/0/4af0dd6fbd5e310a80b6cd2caf413bcf7183d632_1314976_5566.jpg
        $path = __DIR__ . '/../DataFixtures/ApiClientMock/media-files/' . $code;
        if (!file_exists($path)) {
            throw new HttpException("File '$path' does not exists.", new Request('GET', '/'), new Response(404));
        }

        return new Response(200, [], file_get_contents($path));
    }

    public function get(string $code): array
    {
        // TODO: Implement get() method.
    }

    public function listPerPage(int $limit = 10, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        // TODO: Implement all() method.
    }

    public function create($mediaFile, array $data): string
    {
        // TODO: Implement create() method.
    }
}
