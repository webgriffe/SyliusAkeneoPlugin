<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ValueHandlerInterface
{
    public function supports($subject, string $attribute, array $value): bool;

    public function handle($subject, string $attribute, array $value);
}
