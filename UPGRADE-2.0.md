# UPGRADE FROM `v1.14.0` TO `v2.0.0`

## Codebase

### Use PHP 8 syntax (#128)

#### TL;DR
Refactored the codebase to use PHP 8 syntax.

#### BC Breaks

##### Changed
 - [BC] The return type of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
 - [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to a non-contravariant array|bool|int|string
 - [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
 - [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#convert() changed from no type to a non-contravariant array|bool|int|string

### API client replacement (#125)

#### TL;DR
Removed our API Client interface in favor of the Akeneo PHP SDK client.

#### BC Breaks

##### Changed
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverter#__construct() changed from Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Product\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
 - [BC] The parameter $apiClient of Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\ApiClientInterface to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface

##### Removed
 - [BC] Class Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface has been deleted
 - [BC] Class Webgriffe\SyliusAkeneoPlugin\ApiClientInterface has been deleted
 - [BC] Class Webgriffe\SyliusAkeneoPlugin\MeasurementFamiliesApiClientInterface has been deleted
 - [BC] Class Webgriffe\SyliusAkeneoPlugin\ApiClient has been deleted
 - [BC] Class Webgriffe\SyliusAkeneoPlugin\FamilyAwareApiClientInterface has been deleted

### Remove deprecations (#130)

#### TL;DR
Removed all deprecations of the v1.x releases.

#### BC Breaks

##### Changed
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\Controller\ProductEnqueueController#__construct() increased from 3 to 4
 - [BC] The parameter $translator of Webgriffe\SyliusAkeneoPlugin\Controller\ProductEnqueueController#__construct() changed from Symfony\Contracts\Translation\TranslatorInterface|null to a non-contravariant Symfony\Contracts\Translation\TranslatorInterface
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#__construct() increased from 0 to 1
 - [BC] The parameter $translator of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#__construct() changed from Symfony\Contracts\Translation\TranslatorInterface|null to a non-contravariant Symfony\Contracts\Translation\TranslatorInterface
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\Product\Importer#__construct() increased from 12 to 13
 - [BC] The parameter $variantStatusResolver of Webgriffe\SyliusAkeneoPlugin\Product\Importer#__construct() changed from Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface|null to a non-contravariant Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler#__construct() increased from 3 to 4
 - [BC] The parameter $valueConverter of Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler#__construct() changed from Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface|null to a non-contravariant Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler#__construct() increased from 4 to 5
 - [BC] The parameter $propertyAccessor of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler#__construct() changed from Symfony\Component\PropertyAccess\PropertyAccessorInterface|null to a non-contravariant Symfony\Component\PropertyAccess\PropertyAccessorInterface
 - [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler#__construct() increased from 5 to 7
 - [BC] The parameter $translationLocaleProvider of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler#__construct() changed from Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface|null to a non-contravariant Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface
 - [BC] The parameter $translator of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler#__construct() changed from Symfony\Contracts\Translation\TranslatorInterface|null to a non-contravariant Symfony\Contracts\Translation\TranslatorInterface
 
##### Removed
 - [BC] Property Webgriffe\SyliusAkeneoPlugin\DependencyInjection\WebgriffeSyliusAkeneoExtension::$valueHandlersTypesDefinitions was removed
 - [BC] Removed the service `webgriffe_sylius_akeneo_plugin.repository.cleanable_queue_item`, use the `webgriffe_sylius_akeneo.repository.cleanable_queue_item` instead.
 - [BC] Removed the service `webgriffe_sylius_akeneo_plugin.controller.product_enqueue_controller`, use the `webgriffe_sylius_akeneo.controller.product_import_controller` instead.
 - [BC] Removed the resource `webgriffe_sylius_akeneo_plugin.queue_item` use the `webgriffe_sylius_akeneo.queue_item` instead.

### Test changes

#### TL;DR
Edits made on test classes during the previous changes.

#### BC Breaks

##### Removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findProductModel() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findFamilyVariant() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findAttribute() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findProduct() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findAttributeOption() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#downloadFile() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findProductsModifiedSince() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findAllAttributeOptions() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findAllAttributes() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findAllFamilies() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#findFamily() was removed
 - [BC] Method Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock#getMeasurementFamilies() was removed
 - [BC] These ancestors of Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock have been removed: ["Webgriffe\\SyliusAkeneoPlugin\\ApiClientInterface","Webgriffe\\SyliusAkeneoPlugin\\AttributeOptions\\ApiClientInterface","Webgriffe\\SyliusAkeneoPlugin\\FamilyAwareApiClientInterface","Webgriffe\\SyliusAkeneoPlugin\\MeasurementFamiliesApiClientInterface"]
