<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api;

use Akeneo\Pim\ApiClient\Api\Operation\CreatableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\DeletableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\GettableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\UpsertableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\UpsertableResourceListInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use ArrayIterator;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Promise\Promise;
use Psr\Http\Message\StreamInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ResourceInterface;

/**
 * @template T of ResourceInterface
 */
abstract class InMemoryApi implements
    ListableResourceInterface,
    GettableResourceInterface,
    CreatableResourceInterface,
    UpsertableResourceInterface,
    UpsertableResourceListInterface,
    DeletableResourceInterface
{
    /**
     * @return array<string, T>
     */
    abstract public function getResources(): array;

    abstract public static function clear(): void;

    /** @return class-string */
    abstract protected function getResourceClass(): string;

    abstract public static function addResource(ResourceInterface $resource): void;

    public function create(string $code, array $data = []): int
    {
        $class = $this->getResourceClass();
        $resources = $this->getResources();
        $resources[$code] = call_user_func([$class, 'create'], [$code, $data]);

        return 201;
    }

    public function delete(string $code): int
    {
        $resources = $this->getResources();
        if (!array_key_exists($code, $resources)) {
            throw $this->createNotFoundException($code);
        }
        unset($resources[$code]);

        return 204;
    }

    public function listPerPage(int $limit = 100, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 100, array $queryParameters = []): ResourceCursorInterface
    {
        $resources = [];
        foreach ($this->getResources() as $resource) {
            $resources[] = $resource->__serialize();
        }
        /*if ($queryParameters !== []) {
            Assert::count($queryParameters['search'], 1, 'Only one query parameter is supported');
            $search = $queryParameters['search'];
            Assert::keyExists($search, 'updated', 'Only updated search is supported');
            $searchUpdated = $search['updated'];
            $resources = array_filter($resources, static function (array $resource) use ($searchUpdated): bool {
                return new DateTime($resource['updated']) >= new DateTime($searchUpdated[0]['value']);
            });
        }*/

        return new class(new ArrayIterator($resources), $pageSize) implements ResourceCursorInterface {
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

    public function get(string $code, array $queryParameters = []): array
    {
        $resources = $this->getResources();
        if (!array_key_exists($code, $resources)) {
            throw $this->createNotFoundException($code);
        }

        return $resources[$code]->__serialize();
    }

    public function upsert(string $code, array $data = []): int
    {
        // TODO: Implement upsert() method.
    }

    public function upsertAsync(string $code, array $data = []): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsync() method.
    }

    public function upsertList(StreamInterface|array $resources): \Traversable
    {
        // TODO: Implement upsertList() method.
    }

    public function upsertAsyncList(StreamInterface|array $resources): PromiseInterface|Promise
    {
        // TODO: Implement upsertAsyncList() method.
    }

    private function createNotFoundException(string $resource): NotFoundHttpException
    {
        return new NotFoundHttpException(sprintf('Resource %s not found', $resource), new Request('GET', '/'), new Response(404));
    }
}
