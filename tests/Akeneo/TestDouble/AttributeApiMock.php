<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Akeneo\TestDouble;

use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use ArrayIterator;

final class AttributeApiMock implements AttributeApiInterface
{
    public function create(string $code, array $data = []): int
    {
        // TODO: Implement create() method.
    }

    public function get(string $code): array
    {
        return ApiClientMock::jsonFileOrHttpNotFoundException(
            __DIR__ . '/../Data/Attribute/' . $code . '.json',
        );
    }

    public function listPerPage(int $limit = 10, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        $files = glob(__DIR__ . '/../Data/Attribute/*.json');
        $attributes = [];
        foreach ($files as $file) {
            $attributes[] = json_decode(file_get_contents($file), true);
        }

        return new class(new ArrayIterator($attributes), $pageSize) implements ResourceCursorInterface {
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

    public function upsert(string $code, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertList($resources): \Traversable
    {
        // TODO: Implement upsertList() method.
    }
}
