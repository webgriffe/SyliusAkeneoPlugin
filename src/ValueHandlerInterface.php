<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ValueHandlerInterface
{
    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool;

    /**
     * @param mixed $subject
     */
    public function handle($subject, string $attribute, array $value): void;
}
