<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class ApiClient implements ApiClientInterface
{
    public function findProductModel(string $code): ?array
    {
        // TODO: Implement findProductModelByIdentifier() method.
        return null;
    }

    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array
    {
        // TODO: Implement findFamilyVariant() method.
        return null;
    }

    public function findAttribute(string $code): ?array
    {
        // TODO: Implement findAttribute() method.
        return null;
    }

    public function downloadFile(string $url): \SplFileInfo
    {
        // TODO: Implement downloadFile() method.
        return new \SplFileInfo('');
    }

    public function findProduct(string $code): ?array
    {
        // TODO: Implement findProduct() method.
        return null;
    }

    public function findAttributeOption(string $attributeCode, string $optionCode): ?array
    {
        // TODO: Implement findAttributeOption() method.
        return null;
    }

    public function findProductsModifiedAfter(\DateTime $date): ?array
    {
        // TODO: Implement findProductsModifiedAfter() method.
        return null;
    }
}
