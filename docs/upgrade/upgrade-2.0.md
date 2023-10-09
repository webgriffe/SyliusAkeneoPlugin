---
title: Upgrade to 2.0
layout: page
nav_order: 0
parent: Upgrade
---

# Upgrade from `v1.16.2` to `v2.0.0`

In the 2.0 version, we have introduced the Symfony Messenger component and removed all deprecations.

Also, in the 2.0 version, we introduced, with [this change](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/169), Product and ProductVariant validation.
So, **all the products will be validated as they would be when you create or update them from the Sylius admin panel**.
This means that if your Akeneo products have some missing or invalid attribute values, the import will fail.
For example, on Akeneo PIM, product codes can contain dots (.) but in Sylius, they are not allowed; so if you have a product with a code like `foo.bar` in Akeneo, the import will now fail.
In such situations you have to customize Sylius Product and ProductVariant validation (for example in this case to allow dots in product codes).

Here you can find all the steps to upgrade your plugin to v2.0 starting from v1.16.2.
Documented steps are for a simple project without customizations. In this case, you can find more in the below section [Codebase](#Codebase) which
contains all the detailed changes applied to this version and all the BC breaks contained in this major version.

## Simple upgrade

Remove occurrences of `Resources` in `config/packages/webgriffe_sylius_akeneo_plugin.yaml`:

```diff
-    - { resource: "@WebgriffeSyliusAkeneoPlugin/Resources/config/config.yaml" }
+    - { resource: "@WebgriffeSyliusAkeneoPlugin/config/config.yaml" }
```

Remove occurrences of `Resources` in `config/routes/webgriffe_sylius_akeneo_plugin.yaml` or where you import the plugin routes:

```diff
webgriffe_sylius_akeneo_plugin_admin:
-    resource: "@WebgriffeSyliusAkeneoPlugin/Resources/config/admin_routing.yaml"
+    resource: "@WebgriffeSyliusAkeneoPlugin/config/admin_routing.yaml"
```

Be sure that your configuration in `config/packages/webgriffe_sylius_akeneo_plugin.yaml` is already using the new name arguments
as specified [here](https://github.com/webgriffe/SyliusAkeneoPlugin/releases/tag/1.13.0).

Replace all occurrences of route name `webgriffe_sylius_akeneo_product_enqueue`
with `webgriffe_sylius_akeneo_product_import` and translation `webgriffe_sylius_akeneo.ui.enqueue`
with `webgriffe_sylius_akeneo.ui.schedule_import` in your codebase.

Run migration diff command and then execute it:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

Now you have to update your crontab configuration by following this example:

```
0   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:import --all --importer="AttributeOptions"
*   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:import --since-file=/path/to/sylius/var/storage/akeneo-import-sincefile.txt --importer="Product" --importer="ProductAssociations"
0   */6 *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:reconcile
0   0   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:cleanup-item-import-results
```

If everything works well you have just completed your upgrade! Obviously, we suggest that you check the import of some products,
product associations and attribute options before considering the upgrade complete 😉.

## Codebase

### Use PHP 8 syntax ([#128](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/128))

#### TL;DR
Refactored the codebase to use PHP 8 syntax.

#### BC Breaks

##### Changed
- [BC] The return type of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to a non-contravariant array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#convert() changed from no type to a non-contravariant array|bool|int|string

### API client replacement ([#125](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/125))

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

### Remove deprecations ([#130](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/130))

#### TL;DR
Removed all deprecations of the v1.x releases.

#### BC Breaks

##### Changed
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

### Others ([#134](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/134), [#147](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/147), [#150](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/150))

#### TL;DR
- The route `webgriffe_sylius_akeneo_product_enqueue` has been renamed in `webgriffe_sylius_akeneo_product_import`.
- The grid resource route `webgriffe_sylius_akeneo_admin_queue_item` has been removed.
- The grid template action `enqueueProduct` has been renamed in `importProduct`.
- The grid `webgriffe_sylius_akeneo_admin_queue_item` has been removed.
- The `sylius_admin_product` grid action `enqueue` has been renamed in `import`.
- A new `Webgriffe\SyliusAkeneoPlugin\Message\ItemImport` Symfony Messenger message has been added.
- The config `bind_arguments_by_name` under the `webgriffe_sylius_akeneo_plugin` has been removed.
- The config `resources` under the `webgriffe_sylius_akeneo_plugin` has been removed.
- Messages are changed, please view the new `translations/messages.en.yaml` file.
- The commands `webgriffe:akeneo:consume` and `webgriffe:akeneo:queue-cleanup` has been removed.
- The command `webgriffe:akeneo:enqueue` has been renamed to `webgriffe:akeneo:import`.

#### BC Breaks

##### Changed
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler#__construct() increased from 4 to 5
- [BC] The parameter $akeneoAttributeCode of Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler#__construct() changed from string to a non-contravariant Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler#__construct() increased from 5 to 6
- [BC] The parameter $akeneoAttributeCode of Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler#__construct() changed from string to a non-contravariant Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface

##### Removed
- [BC] Method Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension#registerResources() was removed
- [BC] These ancestors of Webgriffe\SyliusAkeneoPlugin\DependencyInjection\WebgriffeSyliusAkeneoExtension have been removed: ["Sylius\\Bundle\\ResourceBundle\\DependencyInjection\\Extension\\AbstractResourceExtension"]
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Menu\AdminMenuListener has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\EventSubscriber\CommandEventSubscriber has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Controller\ProductEnqueueController has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Doctrine\ORM\QueueItemRepository has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Command\QueueCleanupCommand has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Repository\CleanableQueueItemRepositoryInterface has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface has been deleted
- [BC] Class Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface has been deleted

### Remove only temporary files from current akeneo entity import ([#176](https://github.com/webgriffe/SyliusAkeneoPlugin/pull/176))

#### TL;DR

Now, the Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface service requires a file identifier to remove only
the temporary files related to the current Akeneo entity import.

#### BC Breaks

##### Changed
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager#generateTemporaryFilePath() increased from 0 to 1
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager#deleteAllTemporaryFiles() increased from 0 to 1
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface#generateTemporaryFilePath() increased from 0 to 1
- [BC] The number of required arguments for Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface#deleteAllTemporaryFiles() increased from 0 to 1


### Test changes

#### TL;DR
Edits made on test classes during the previous changes.

#### BC Breaks

##### Removed
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\QueueItem\IndexPage has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\QueueItem\IndexPageInterface has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup\QueueContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db\QueueContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli\QueueCleanupCommandContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli\ConsumeCommandContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli\EnqueueCommandContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\System\DateTimeContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Transform\QueueItemContext has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin\ManagingQueueItems has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DependencyInjection\CompilerPassTest has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Integration\DependencyInjection\ExtensionTest has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\DateTimeBuilder has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock has been deleted
- [BC] Class Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TemporaryFilesManagerTest has been deleted
