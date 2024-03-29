<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AppCatalog\AppCatalogApiInterface;
use Akeneo\Pim\ApiClient\Api\AppCatalog\AppCatalogProductApiInterface;
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
use Akeneo\Pim\ApiClient\Api\Operation\DownloadableResourceInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductDraftUuidApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductUuidApiInterface;
use Akeneo\Pim\ApiClient\Api\PublishedProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityMediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityRecordApiInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeOptionApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryFamilyApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryFamilyVariantApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductMediaFileApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductModelApi;

final class InMemoryAkeneoPimClient implements AkeneoPimClientInterface
{
    public function getToken(): ?string
    {
    }

    public function getRefreshToken(): ?string
    {
    }

    public function getProductApi(): ProductApiInterface
    {
        return new InMemoryProductApi();
    }

    public function getCategoryApi(): CategoryApiInterface
    {
    }

    public function getCategoryMediaFileApi(): DownloadableResourceInterface
    {
    }

    public function getAttributeApi(): AttributeApiInterface
    {
        return new InMemoryAttributeApi();
    }

    public function getAttributeOptionApi(): AttributeOptionApiInterface
    {
        return new InMemoryAttributeOptionApi();
    }

    public function getAttributeGroupApi(): AttributeGroupApiInterface
    {
    }

    public function getFamilyApi(): FamilyApiInterface
    {
        return new InMemoryFamilyApi();
    }

    public function getProductMediaFileApi(): MediaFileApiInterface
    {
        return new InMemoryProductMediaFileApi();
    }

    public function getLocaleApi(): LocaleApiInterface
    {
    }

    public function getChannelApi(): ChannelApiInterface
    {
    }

    public function getCurrencyApi(): CurrencyApiInterface
    {
    }

    public function getMeasureFamilyApi(): MeasureFamilyApiInterface
    {
    }

    public function getMeasurementFamilyApi(): MeasurementFamilyApiInterface
    {
    }

    public function getAssociationTypeApi(): AssociationTypeApiInterface
    {
    }

    public function getFamilyVariantApi(): FamilyVariantApiInterface
    {
        return new InMemoryFamilyVariantApi();
    }

    public function getProductModelApi(): ProductModelApiInterface
    {
        return new InMemoryProductModelApi();
    }

    public function getPublishedProductApi(): PublishedProductApiInterface
    {
    }

    public function getProductModelDraftApi(): ProductModelDraftApiInterface
    {
    }

    public function getProductDraftApi(): ProductDraftApiInterface
    {
    }

    public function getAssetApi(): AssetApiInterface
    {
    }

    public function getAssetCategoryApi(): AssetCategoryApiInterface
    {
    }

    public function getAssetTagApi(): AssetTagApiInterface
    {
    }

    public function getAssetReferenceFileApi(): AssetReferenceFileApiInterface
    {
    }

    public function getAssetVariationFileApi(): AssetVariationFileApiInterface
    {
    }

    public function getReferenceEntityRecordApi(): ReferenceEntityRecordApiInterface
    {
    }

    public function getReferenceEntityMediaFileApi(): ReferenceEntityMediaFileApiInterface
    {
    }

    public function getReferenceEntityAttributeApi(): ReferenceEntityAttributeApiInterface
    {
    }

    public function getReferenceEntityAttributeOptionApi(): ReferenceEntityAttributeOptionApiInterface
    {
    }

    public function getReferenceEntityApi(): ReferenceEntityApiInterface
    {
    }

    public function getAssetManagerApi(): AssetManagerApiInterface
    {
    }

    public function getAssetFamilyApi(): AssetFamilyApiInterface
    {
    }

    public function getAssetAttributeApi(): AssetAttributeApiInterface
    {
    }

    public function getAssetAttributeOptionApi(): AssetAttributeOptionApiInterface
    {
    }

    public function getAssetMediaFileApi(): AssetMediaFileApiInterface
    {
    }

    public function getProductUuidApi(): ProductUuidApiInterface
    {
    }

    public function getProductDraftUuidApi(): ProductDraftUuidApiInterface
    {
    }

    public function getAppCatalogApi(): AppCatalogApiInterface
    {
    }

    public function getAppCatalogProductApi(): AppCatalogProductApiInterface
    {
    }
}
