<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use ArrayIterator;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Promise\Promise;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeOption;

final class InMemoryAttributeOptionApi implements AttributeOptionApiInterface
{
    /** @var array<string, <string, AttributeOption>> */
    public static array $attributeOptions = [];

    public static function addResource(AttributeOption $attributeOption): void
    {
        self::$attributeOptions[$attributeOption->attribute][$attributeOption->code] = $attributeOption;
    }

    public function get($attributeCode, $code): array
    {
        if (!array_key_exists($attributeCode, self::$attributeOptions)) {
            throw $this->createNotFoundException();
        }
        $attributeOptions = self::$attributeOptions[$attributeCode];
        if (!array_key_exists($code, $attributeOptions)) {
            throw $this->createNotFoundException();
        }

        return (array) $attributeOptions[$code];
    }

    public function create($attributeCode, $attributeOptionCode, array $data = []): int
    {
        self::$attributeOptions[] = AttributeOption::create($attributeCode, $attributeOptionCode, $data);

        return 201;
    }

    public function listPerPage($attributeCode, $limit = 100, $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all($attributeCode, $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        $attributeOptions = [];
        foreach (self::$attributeOptions[$attributeCode] as $attributeOption) {
            $attributeOptions[] = $attributeOption->__serialize();
        }

        return new class(new ArrayIterator($attributeOptions), $pageSize) implements ResourceCursorInterface {
            public function __construct(private ArrayIterator $iterator, private int $pageSize)
            {
            }

            public function current(): mixed
            {
                return $this->iterator->current();
            }

            public function next(): void
            {
                $this->iterator->next();
            }

            public function key(): mixed
            {
                return $this->iterator->key();
            }

            public function valid(): bool
            {
                return $this->iterator->valid();
            }

            public function rewind(): void
            {
                $this->iterator->rewind();
            }

            public function getPageSize(): ?int
            {
                return $this->pageSize;
            }
        };
    }

    public function upsert($attributeCode, $attributeOptionCode, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertAsync($attributeCode, $attributeOptionCode, array $data = []): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsync() method.
    }

    public function upsertList($attributeCode, $attributeOptions): \Traversable
    {
        // TODO: Implement upsertList() method.
    }

    public function upsertAsyncList($attributeCode, $attributeOptions): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsyncList() method.
    }

    private function createNotFoundException(): NotFoundHttpException
    {
        return new NotFoundHttpException('Attribute option not found', new Request('GET', '/'), new Response(404));
    }
}
