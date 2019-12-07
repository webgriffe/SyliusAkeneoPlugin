<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ImporterInterface
{
    public function import(string $identifier): void;
}
