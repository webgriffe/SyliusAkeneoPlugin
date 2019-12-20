<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;

final class ApiClientMock implements ApiClientInterface
{
    public function findProductModel(string $code): ?array
    {
        return $this->jsonDecodeOrNull(__DIR__ . '/../DataFixtures/ApiClientMock/ProductModel/' . $code . '.json');
    }

    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array
    {
        return $this->jsonDecodeOrNull(
            __DIR__ . '/../DataFixtures/ApiClientMock/FamilyVariant/' . $familyCode . '/' . $familyVariantCode . '.json'
        );
    }

    public function findAttribute(string $code): ?array
    {
        return $this->jsonDecodeOrNull(__DIR__ . '/../DataFixtures/ApiClientMock/Attribute/' . $code . '.json');
    }

    /**
     * @return mixed|null
     */
    private function jsonDecodeOrNull(string $filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true);
        }

        return null;
    }
}
