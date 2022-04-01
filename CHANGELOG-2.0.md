# UPGRADE FROM `v1.13.3` TO `v2.0.0`

## Codebase

### Use PHP 8 syntax (#128)

#### Changed
- [BC] The return type of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to a non-contravariant array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#convert() changed from no type to a non-contravariant array|bool|int|string

### API client replacement (#125)

#### Changed
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverter#__construct() changed from Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Product\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
- [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface

#### Removed
- [BC] Class Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\ApiClientInterface has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\ApiClient has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\FamilyAwareApiClientInterface has been deleted
