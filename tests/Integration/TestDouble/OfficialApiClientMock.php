<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AssetApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetCategoryApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetApiInterface as AssetManagerApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetAttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetAttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetMediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetReferenceFileApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetTagApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetVariationFileApiInterface;
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
use Akeneo\Pim\ApiClient\Api\ProductDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\PublishedProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityMediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityRecordApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class OfficialApiClientMock implements AkeneoPimClientInterface
{
    private ProductApiInterface $productApi;

    private MediaFileApiInterface $productMediaFileApi;

    private AttributeOptionApiInterface $attributeOptionApi;

    private AttributeApiInterface $attributeApi;

    private ProductModelApiInterface $productModelApi;

    private FamilyVariantApiInterface $familyVariantApi;

    private FamilyApiInterface $familyApi;

    public function __construct()
    {
        $this->productApi = new ProductApiMock();
        $this->productMediaFileApi = new ProductMediaFileApiMock();
        $this->attributeOptionApi = new AttributeOptionApiMock();
        $this->attributeApi = new AttributeApiMock();
        $this->productModelApi = new ProductModelApiMock();
        $this->familyVariantApi = new FamilyVariantApiMock();
        $this->familyApi = new FamilyApiMock();
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
        return $this->familyApi;
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

        return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getPublishedProductApi(): PublishedProductApiInterface
    {
        // TODO: Implement getPublishedProductApi() method.
    }

    public function getProductModelDraftApi(): ProductModelDraftApiInterface
    {
        // TODO: Implement getProductModelDraftApi() method.
    }

    public function getProductDraftApi(): ProductDraftApiInterface
    {
        // TODO: Implement getProductDraftApi() method.
    }

    public function getAssetApi(): AssetApiInterface
    {
        // TODO: Implement getAssetApi() method.
    }

    public function getAssetCategoryApi(): AssetCategoryApiInterface
    {
        // TODO: Implement getAssetCategoryApi() method.
    }

    public function getAssetTagApi(): AssetTagApiInterface
    {
        // TODO: Implement getAssetTagApi() method.
    }

    public function getAssetReferenceFileApi(): AssetReferenceFileApiInterface
    {
        // TODO: Implement getAssetReferenceFileApi() method.
    }

    public function getAssetVariationFileApi(): AssetVariationFileApiInterface
    {
        // TODO: Implement getAssetVariationFileApi() method.
    }

    public function getReferenceEntityRecordApi(): ReferenceEntityRecordApiInterface
    {
        // TODO: Implement getReferenceEntityRecordApi() method.
    }

    public function getReferenceEntityMediaFileApi(): ReferenceEntityMediaFileApiInterface
    {
        // TODO: Implement getReferenceEntityMediaFileApi() method.
    }

    public function getReferenceEntityAttributeApi(): ReferenceEntityAttributeApiInterface
    {
        // TODO: Implement getReferenceEntityAttributeApi() method.
    }

    public function getReferenceEntityAttributeOptionApi(): ReferenceEntityAttributeOptionApiInterface
    {
        // TODO: Implement getReferenceEntityAttributeOptionApi() method.
    }

    public function getReferenceEntityApi(): ReferenceEntityApiInterface
    {
        // TODO: Implement getReferenceEntityApi() method.
    }

    public function getAssetManagerApi(): AssetManagerApiInterface
    {
        // TODO: Implement getAssetManagerApi() method.
    }

    public function getAssetFamilyApi(): AssetFamilyApiInterface
    {
        // TODO: Implement getAssetFamilyApi() method.
    }

    public function getAssetAttributeApi(): AssetAttributeApiInterface
    {
        // TODO: Implement getAssetAttributeApi() method.
    }

    public function getAssetAttributeOptionApi(): AssetAttributeOptionApiInterface
    {
        // TODO: Implement getAssetAttributeOptionApi() method.
    }

    public function getAssetMediaFileApi(): AssetMediaFileApiInterface
    {
        // TODO: Implement getAssetMediaFileApi() method.
    }
}
