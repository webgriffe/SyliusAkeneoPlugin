<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ValueHandlerResolverInterface
{
    /**
     * @param mixed $subject
     */
    public function resolve($subject, string $attribute, array $value): ?ValueHandlerInterface;
}
