<?php

declare(strict_types=1);


namespace Webgriffe\SyliusAkeneoPlugin;

interface ApiClientInterface
{
    public function findProductModelByIdentifier(string $identifier): ?array;
}