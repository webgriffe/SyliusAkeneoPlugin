<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;


interface FamilyAwareApiClientInterface
{
    public function findAllFamilies(): array;

    public function findFamily(string $code): ?array;
}
