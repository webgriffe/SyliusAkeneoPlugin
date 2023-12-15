<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ValueHandlersResolverInterface
{
    /**
     * @param mixed $subject
     *
     * @return ValueHandlerInterface[]
     */
    public function resolve($subject, string $attribute, array $value): array;

    /**
     * @return ValueHandlerInterface[]
     */
    public function getValueHandlers(): array;
}
