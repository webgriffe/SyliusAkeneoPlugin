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
use Webmozart\Assert\Assert;

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
     * @var array<string, T>
     */
    public static array $resources = [];

    /** @return class-string */
    abstract protected function getResourceClass(): string;

    public static function addResource(ResourceInterface $resource)
    {
        self::$resources[$resource->getIdentifier()] = $resource;
    }

    public function create(string $code, array $data = []): int
    {
        $class = $this->getResourceClass();
        Assert::isInstanceOf($class, ResourceInterface::class);

        self::$resources[] = call_user_func([$class, 'create'], [$code, $data]);

        return 201;
    }

    public function delete(string $code): int
    {
        if (!array_key_exists($code, self::$resources)) {
            throw $this->createNotFoundException($code);
        }
        unset(self::$resources[$code]);

        return 204;
    }

    public function listPerPage(int $limit = 100, bool $withCount = false, array $queryParameters = []): PageInterface
    {
        // TODO: Implement listPerPage() method.
    }

    public function all(int $pageSize = 100, array $queryParameters = []): ResourceCursorInterface
    {
        return new class(new ArrayIterator(self::$resources), $pageSize) implements ResourceCursorInterface {
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

    public function get(string $code, array $queryParameters = []): array
    {
        if (!array_key_exists($code, self::$resources)) {
            throw $this->createNotFoundException($code);
        }

        return self::$resources[$code]->__serialize();
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
