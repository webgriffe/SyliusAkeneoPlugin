<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeOptions;

interface ApiClientInterface
{
    public function findAllAttributeOptions(string $attributeCode): array;

    public function findAllAttributes(): array;
}
