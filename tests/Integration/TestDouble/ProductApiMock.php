<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use ArrayIterator;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

final class ProductApiMock implements ProductApiInterface
{
    private array $productsUpdatedAt = [];

    public function addProductUpdatedAt(string $identifier, DateTime $updatedAt): void
    {
        $this->productsUpdatedAt[$identifier] = $updatedAt;
    }

    public function create(string $code, array $data = []): int
    {
        throw new RuntimeException(sprintf('"%s" not implemented yet', __METHOD__));
    }

    public function delete(string $code): int
    {
        throw new RuntimeException(sprintf('"%s" not implemented yet', __METHOD__));
    }

    public function get(string $code, array $queryParameters = []): array
    {
        return ApiClientMock::jsonFileOrHttpNotFoundException(
            __DIR__ . '/../DataFixtures/ApiClientMock/Product/' . $code . '.json',
        );
    }

    public function listPerPage(int $limit = 10, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        throw new RuntimeException(sprintf('"%s" not implemented yet', __METHOD__));
    }

    public function all(int $pageSize = 10, array $queryParameters = []): ResourceCursorInterface
    {
        $date = $queryParameters['search']['updated_at'][0]['value'] ?? null;
        if ($date === null) {
            throw new InvalidArgumentException('Only filtering by updated at is supported by this mock');
        }
        $date = new DateTime($date);
        $products = [];
        foreach ($this->productsUpdatedAt as $identifier => $updatedAt) {
            if ($updatedAt > $date) {
                $products[] = ['identifier' => $identifier];
            }
        }

        return new class(new ArrayIterator($products), $pageSize) implements ResourceCursorInterface {
            /** @var ArrayIterator */
            private $iterator;

            /** @var int */
            private $pageSize;

            public function __construct(ArrayIterator $iterator, int $pageSize)
            {
                $this->iterator = $iterator;
                $this->pageSize = $pageSize;
            }

            public function current()
            {
                return $this->iterator->current();
            }

            public function next()
            {
                $this->iterator->next();
            }

            public function key()
            {
                return $this->iterator->key();
            }

            public function valid()
            {
                return $this->iterator->valid();
            }

            public function rewind()
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
        throw new RuntimeException(sprintf('"%s" not implemented yet', __METHOD__));
    }

    public function upsertList($resources): \Traversable
    {
        throw new RuntimeException(sprintf('"%s" not implemented yet', __METHOD__));
    }
}
