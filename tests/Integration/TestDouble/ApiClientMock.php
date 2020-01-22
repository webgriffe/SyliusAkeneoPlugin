<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Symfony\Component\HttpFoundation\File\File;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;

final class ApiClientMock implements ApiClientInterface
{
    private $productsUpdatedAt = [];

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

    public function findProduct(string $code): ?array
    {
        return $this->jsonDecodeOrNull(__DIR__ . '/../DataFixtures/ApiClientMock/Product/' . $code . '.json');
    }

    public function findAttributeOption(string $attributeCode, string $optionCode): ?array
    {
        return $this->jsonDecodeOrNull(
            __DIR__ . '/../DataFixtures/ApiClientMock/AttributeOption/' . $attributeCode . '/' . $optionCode . '.json'
        );
    }

    public function addProductUpdatedAt(string $identifier, \DateTime $updatedAt): void
    {
        $this->productsUpdatedAt[$identifier] = $updatedAt;
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

    public function downloadFile(string $url): \SplFileInfo
    {
        // URL should be like http://demo.akeneo.com/api/rest/v1/media-files/4/a/f/0/4af0dd6fbd5e310a80b6cd2caf413bcf7183d632_1314976_5566.jpg/download
        $path = str_replace(['/api/rest/v1/media-files/', '/download'], '', parse_url($url, \PHP_URL_PATH));
        $path = __DIR__ . '/../DataFixtures/ApiClientMock/media-files/' . $path;
        if (!file_exists($path)) {
            throw new \RuntimeException("File '$path' does not exists.");
        }
        $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
        file_put_contents($tempName, file_get_contents($path));

        return new File($tempName);
    }
}
