<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AssociationTypeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeGroupApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Akeneo\Pim\ApiClient\Api\ChannelApiInterface;
use Akeneo\Pim\ApiClient\Api\CurrencyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\LocaleApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasureFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasurementFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class OfficialApiClientMock implements AkeneoPimClientInterface
{
    /** @var ProductApiInterface */
    private $productApi;

    /** @var MediaFileApiInterface */
    private $productMediaFileApi;

    /** @var AttributeOptionApiInterface */
    private $attributeOptionApi;

    /** @var AttributeApiInterface */
    private $attributeApi;

    /** @var ProductModelApiInterface */
    private $productModelApi;

    /** @var FamilyVariantApiInterface */
    private $familyVariantApi;

    public function __construct()
    {
        $this->productApi = new ProductApiMock();
        $this->productMediaFileApi = new ProductMediaFileApiMock();
        $this->attributeOptionApi = new AttributeOptionApiMock();
        $this->attributeApi = new AttributeApiMock();
        $this->productModelApi = new ProductModelApiMock();
        $this->familyVariantApi = new FamilyVariantApiMock();
    }

    public function addProductUpdatedAt(string $identifier, \DateTime $updatedAt): void
    {
        $this->productApi->addProductUpdatedAt($identifier, $updatedAt);
    }

    public function getToken(): ?string
    {
        // TODO: Implement getToken() method.
    }

    public function getRefreshToken(): ?string
    {
        // TODO: Implement getRefreshToken() method.
    }

    public function getProductApi(): ProductApiInterface
    {
        return $this->productApi;
    }

    public function getCategoryApi(): CategoryApiInterface
    {
        // TODO: Implement getCategoryApi() method.
    }

    public function getAttributeApi(): AttributeApiInterface
    {
        return $this->attributeApi;
    }

    public function getAttributeOptionApi(): AttributeOptionApiInterface
    {
        return $this->attributeOptionApi;
    }

    public function getAttributeGroupApi(): AttributeGroupApiInterface
    {
        // TODO: Implement getAttributeGroupApi() method.
    }

    public function getFamilyApi(): FamilyApiInterface
    {
        // TODO: Implement getFamilyApi() method.
    }

    public function getProductMediaFileApi(): MediaFileApiInterface
    {
        return $this->productMediaFileApi;
    }

    public function getLocaleApi(): LocaleApiInterface
    {
        // TODO: Implement getLocaleApi() method.
    }

    public function getChannelApi(): ChannelApiInterface
    {
        // TODO: Implement getChannelApi() method.
    }

    public function getCurrencyApi(): CurrencyApiInterface
    {
        // TODO: Implement getCurrencyApi() method.
    }

    public function getMeasureFamilyApi(): MeasureFamilyApiInterface
    {
        // TODO: Implement getMeasureFamilyApi() method.
    }

    public function getMeasurementFamilyApi(): MeasurementFamilyApiInterface
    {
        // TODO: Implement getMeasurementFamilyApi() method.
    }

    public function getAssociationTypeApi(): AssociationTypeApiInterface
    {
        // TODO: Implement getAssociationTypeApi() method.
    }

    public function getFamilyVariantApi(): FamilyVariantApiInterface
    {
        return $this->familyVariantApi;
    }

    public function getProductModelApi(): ProductModelApiInterface
    {
        return $this->productModelApi;
    }

    public static function jsonFileOrHttpNotFoundException(string $file): array
    {
        if (!file_exists($file)) {
            throw new HttpException('Not found', new Request('GET', '/'), new Response(404));
        }

        return json_decode(file_get_contents($file), true);
    }
}
