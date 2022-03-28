<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use ArrayIterator;

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
        $attributeOptions = $this->jsonDecodeOrNull(
            __DIR__ . '/../DataFixtures/ApiClientMock/AttributeOption/' . $attributeCode . '.json'
        );

        return new class(new ArrayIterator($attributeOptions), $pageSize) implements ResourceCursorInterface {
            public function __construct(private ArrayIterator $iterator, private int $pageSize)
            {
            }

            public function current()
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

    /** @return mixed|null */
    private function jsonDecodeOrNull(string $filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true, 512, \JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
