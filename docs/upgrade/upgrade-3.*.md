---
title: Upgrade to 3.*
layout: page
nav_order: 0
parent: Upgrade
---

# Upgrade from `v3.0.1` to `v3.0.2`

The `Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager` did not clean up the temporary files downloaded during the product import when the product variant code contained a `/`, a regex special char or was longer than about 40 chars. For codes containing a `/` it also raised a `preg_match(): Unknown modifier` warning on every import, which could make the log files grow considerably.

To fix it, the naming of the temporary files has changed: the product variant code in the file name is now sanitized, shortened and followed by a short hash (e.g. `akeneo-product_variant_ABT_APE_117_0433-<hash>-<random>`).

Temporary files created by previous versions that were never cleaned up are not removed by the plugin, so it's worth removing them manually once, while no import is running, from the temporary directory (the `webgriffe_sylius_akeneo.temporary_directory` parameter, which defaults to the system temporary directory). They are the files named `akeneo-product-variant-*` and, for product variant codes containing a `/`, the files named after the part of the code following the last `/` (e.g. `0433-<random>` for the code `ABT_APE-117/0433`).

# Upgrade from `v2.9.x` to `v3.0`

In this version, we have updated the plugin to be fully compatible with version 2 of Sylius and to use the Sylius test application for plugin development and testing.

- The route `@WebgriffeSyliusAkeneoPlugin/config/admin_routing.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/routes/admin.php`.
- The route `@WebgriffeeSyliusAkeneoPlugin/config/webhook_routing.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/routes/webhook.php`.
- The route `@WebgriffeSyliusAkeneoPlugin/config/config.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/config.php`.
- The template `@WebgriffeSyliusAkeneoPlugin/ItemImportResult/Grid/Field/successful.html.twig` has been renamed to `@WebgriffeSyliusAkeneoPlugin/admin/item_import_result/grid/field/successful.html.twig`.
- The template `@WebgriffeSyliusAkeneoPlugin/Product/Grid/Action/import.html.twig` has been removed.

Please, note also the following BC (previously marked as deprecated) changes:
```
Error: The number of required arguments for Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler#__construct() increased from 4 to 5
Error: The parameter $akeneoPimClient of Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler#__construct() changed from Akeneo\Pim\ApiClient\AkeneoPimClientInterface|null to a non-contravariant Akeneo\Pim\ApiClient\AkeneoPimClientInterface
Error: The number of required arguments for Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() increased from 3 to 9
Error: The parameter $optionRepository of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Sylius\Component\Product\Repository\ProductOptionRepositoryInterface|null to a non-contravariant Sylius\Component\Product\Repository\ProductOptionRepositoryInterface
Error: The parameter $translationLocaleProvider of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface|null to a non-contravariant Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface
Error: The parameter $productOptionValueTranslationFactory of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Sylius\Resource\Factory\FactoryInterface|null to a non-contravariant Sylius\Resource\Factory\FactoryInterface
Error: The parameter $productOptionValueFactory of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Sylius\Resource\Factory\FactoryInterface|null to a non-contravariant Sylius\Resource\Factory\FactoryInterface
Error: The parameter $productOptionTranslationFactory of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Sylius\Resource\Factory\FactoryInterface|null to a non-contravariant Sylius\Resource\Factory\FactoryInterface
Error: The parameter $translator of Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer#__construct() changed from Symfony\Contracts\Translation\TranslatorInterface|null to a non-contravariant Symfony\Contracts\Translation\TranslatorInterface
Error: The number of required arguments for Webgriffe\SyliusAkeneoPlugin\Controller\WebhookController#__construct() increased from 3 to 4
Error: The parameter $eventDispatcher of Webgriffe\SyliusAkeneoPlugin\Controller\WebhookController#__construct() changed from Symfony\Component\EventDispatcher\EventDispatcherInterface|null to a non-contravariant Symfony\Component\EventDispatcher\EventDispatcherInterface
```
