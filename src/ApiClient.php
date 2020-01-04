<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class ApiClient implements ApiClientInterface
{
    public function findProductModel(string $code): ?array
    {
        // TODO: Implement findProductModelByIdentifier() method.
    }

    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array
    {
        // TODO: Implement findFamilyVariant() method.
    }

    public function findAttribute(string $code): ?array
    {
        // TODO: Implement findAttribute() method.
    }

    public function downloadFile(string $url): \SplFileInfo
    {
        // TODO: Implement downloadFile() method.
    }

    public function findProduct(string $code): ?array
    {
        // TODO: Implement findProduct() method.
    }

    public function findAttributeOption(string $attributeCode, string $optionCode): ?array
    {
        // TODO: Implement findAttributeOption() method.
    }
}
