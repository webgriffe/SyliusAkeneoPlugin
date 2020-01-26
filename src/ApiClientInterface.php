<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface ApiClientInterface
{
    public function findProductModel(string $code): ?array;

    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array;

    public function findAttribute(string $code): ?array;

    public function findProduct(string $code): ?array;

    public function downloadFile(string $code): \SplFileInfo;

    public function findAttributeOption(string $attributeCode, string $optionCode): ?array;

    public function findProductsModifiedSince(\DateTime $date): array;
}
