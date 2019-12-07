<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;


interface ValueHandlersRegistryInterface
{
    /**
     * @param ValueHandlerInterface $valueHandler
     * @param int $priority
     */
    public function add(ValueHandlerInterface $valueHandler, int $priority): void;

    /**
     * @return ValueHandlerInterface[]
     */
    public function all(): array;
}
