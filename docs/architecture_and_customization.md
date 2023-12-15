---
title: Architecture & customization
layout: page
nav_order: 6
---

# Architecture & customization

> This plugin makes use of [Symfony Messenger](https://symfony.com/doc/current/messenger.html) component. It is highly
> recommended to have a minimum knowledge of these component to understand how this integration works.

This plugin has basically three entry points:

* The UI admin import button, this will import only products
* The Import CLI command, this will import both product, product associations and attribute options
* The Webhook controller, this will import product and product associations when created/updated on Akeneo

These entry points deals to identify entities to import from Akeneo. When they have collected them they dispatch
an `Webgriffe\SyliusAkeneoPlugin\Message\ItemImport` message on the messenger default bus.
By default, in the configuration this message is handled by the main bus, the same bus used as default by Sylius for
catalog promotions. This means that, if you have configured the main bus to run synchronously the import will be
executed at the same time, otherwise it will be handled by the Messenger queue asynchronously.
We suggest to run it asynchronously especially if you plan to run the import command manually infrequently or with
option --all.

To be able to import entities (or even only different parts of each entity),
the `Webgriffe\SyliusAkeneoPlugin\MessageHandler\ItemImportHandler` (the Messenger message handler) use an **
importer registry** which holds all the registered **importers**.

An importer is a service implementing the `Webgriffe\SyliusAkeneoPlugin\ImporterInterface` and that mainly holds the
logic about how to import its Akeneo entities. If you want to import from Akeneo other entities not implemented in this
plugin you have can implement your own importer. You can also replace an importer provided with this plugin by
decorating or replacing its service definition.

To implement a new custom importer create a class which implements the `Webgriffe\SyliusAkeneoPlugin\ImporterInterface`:

```php
// src/Importer/CustomImporter.php

namespace App\Importer;

use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

final class CustomImporter implements ImporterInterface
{
    // ...
}
```

Then define the importer with the `webgriffe_sylius_akeneo.importer` tag:

```yaml
# config/services.yaml

app.custom.importer:
    class: App\Importer\CustomImporter
    tags:
        - { name: 'webgriffe_sylius_akeneo.importer' }
```

Anyway, this plugin already implement the following importers.

### Product Importer

Akeneo is a PIM (Product Information Management) system so its job is to manage product data. For this reason, this
Sylius
Akeneo plugin is focused on importing products and provides a **product
importer** (`\Webgriffe\SyliusAkeneoPlugin\Product\Importer`).

This product importer processes Akeneo product data through the following components:

#### Taxons resolver

A **taxons resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\TaxonsResolverInterface`) which is responsible to return
the list of Sylius taxons for a given Akeneo product. The provided implementation of the taxons resolver is
the `Webgriffe\SyliusAkeneoPlugin\Product\AlreadyExistingTaxonsResolver` class which returns the already existent Sylius
taxons which have the same code as the related Akeneo categories.

#### Product options resolver

A **product options resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolverInterface`) which is
responsible to return the related Sylius's **product option(s)** when the product being imported is part of a parent
product model. The provided implementation of the product options resolver is
the `Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver` class, which returns already existing product options
but also creates new ones if needed.

#### Channels resolver

A **channels resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\ChannelsResolverInterface`) which is responsible to
return the list of Sylius channels where the products should be enabled. The provided implementation of the channels'
resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AllChannelsResolver` class which simply enables the product in all
available Sylius channels.

#### Status resolver

A **status resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface`) which is responsible to return
the Sylius product status (enabled or disabled, true or false). The provided implementation of the status resolver is
the `Webgriffe\SyliusAkeneoPlugin\Product\StatusResolver` which returns the same product status of the related Akeneo
product, but only if this doesn't belong to a parent product model, otherwise it will always return true (enabled). This
is because in Sylius the product status is at product level and not (also) at product variant level; instead, in Akeneo
the status is only at product level and not at product model level. So, in Akeneo, you could have only one disabled
product variant for a parent product which have several other variants enabled. This situation can't be mapped on Sylius
at present.

#### Value handlers resolver

A **value handlers resolver** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface`) which is responsible to
return a list of **value handlers** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface`) for each Akeneo product
attribute value.

The provided implementation of the value handlers resolver is
the `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` which returns, for each attribute value, the list of
all the value handlers supporting that attribute value sorted by a priority. Each value handler returned by the resolver
for a given attribute is then called to handle that value.

For more detail on how the Product importer works look at the code of
the `Webgriffe\SyliusAkeneoPlugin\Product\Importer::import()` method.

#### Value handlers

By default, the provided `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` is configured with value handlers
specified in the `webgriffe_sylius_akeneo.value_handlers.product` array as explained in the configuration paragraph.
This plugin already provides some value handler implementations that are:

* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler` (type `generic_attribute`) it will automatically
  handle Sylius attributes whose attribute code matches Akeneo attribute code.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler` (type `channel_pricing`): it sets the value
  found on a given Akeneo price attribute (`options.$akeneoAttribute`) as the Sylius product's channels price or
  original price for
  channels whose base currency is the price currency of the Akeneo price.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler` (type `file_attribute`): it saves the file
  downloaded from the Akeneo file attribute (`options.$akeneoAttributeCode`) to the given destination
  path (`options.$downloadPath`).
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler` (type `generic_property`): using
  the [Symfony's Property Access component](https://symfony.com/doc/current/components/property_access.html), it sets
  the value found on a given Akeneo attribute (`options.$akeneoAttributeCode`) on a given property
  path (`options.$propertyPath`) of both product and product variant.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler` (type `image`): it downloads the image found on a given
  Akeneo image attribute (`options.$akeneoAttributeCode`) and sets it as a Sylius product image with a provided type
  string (`options.$syliusImageType`).
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler` (type `immutable_slug`): it slugifies the value
  found on a given Akeneo attribute (`options.$akeneoAttributeToSlugify`) and sets it on the Sylius slug product
  translation property.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\MetricPropertyValueHandler` (type `metric_property`) using
  the [Symfony's Property Access component](https://symfony.com/doc/current/components/property_access.html), it sets
  the value found on a given Akeneo attribute (`options.$akeneoAttributeCode`) on a given property
  path (`options.$propertyPath`) of both product and product variant. It automatically converts it to the default unit
  of measurement or the one specified (`options.$akeneoUnitMeasurementCode`).
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler` (type `product_option`): it sets the value found
  on a given Akeneo attribute as a Sylius product option value on the product variant.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler` (type `translatable_property`): using
  the [Symfony's Property Access component](https://symfony.com/doc/current/components/property_access.html), it sets
  the value found on a given Akeneo attribute (`options.$akeneoAttributeCode`) on a given property
  path (`options.$translationPropertyPath`) of both product and product variant translations.

To add a custom value handler to the resolver you can implement your own by implementing
the `Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface` and then tag it with
the `webgriffe_sylius_akeneo.product.value_handler` tag:

```yaml
# config/services.yaml

app.my_custom_value_handler:
    class: App\MyCustomValueHandler
    tags:
        - { name: 'webgriffe_sylius_akeneo.product.value_handler', priority: 42 }
```

### Product models importer

Another provided importer is the **product models
importer** (`Webgriffe\SyliusAkeneoPlugin\ProductModel\Importer`). This importer imports the Akeneo product models
to the corresponding Sylius products and product variants. Basically, it dispatch an `ItemImport` message for each
product, on Akeneo, belonging to the product model. So it uses the same logic of the product importer described above.

### Product associations importer

Another provided importer is the **product associations
importer** (`Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer`). This importer imports the Akeneo products
associations to the corresponding Sylius products associations. The association types must already exist on Sylius with
the same code they have on Akeneo.

### Attribute options importer

Another provided importer is the **attribute options
importer** (`\Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer`). This importer imports the Akeneo simple select
and multi select attributes options into Sylius select attributes. The select attributes must already exist on Sylius
with the same code they have on Akeneo.

## Customize which Akeneo products to import

### Customize Akeneo products to import from the command

Each built-in importer described above implements a `getIdentiferModifiedSince()` method.
This method is used to identify which Akeneo entity identifiers should be imported or reconciled when the corresponding CLI commands run.
By default, those methods return all the Akeneo identifiers of entities modified since the given date.
But maybe you want to only import a subset of those Akeneo entities.

For example, you might want to only import Akeneo products that have a not-empty family and a completeness of 100% for the `ecommerce` Akeneo channel.
To do so you can define an event listener or subscriber for the `Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent` event and add the corresponding filters to the `SearchBuilder` object:

```php
// src/EventSubscriber/IdentifiersModifiedSinceSearchBuilderBuiltEventSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer as ProductAssociationsImporter;

final class IdentifiersModifiedSinceSearchBuilderBuiltEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            IdentifiersModifiedSinceSearchBuilderBuiltEvent::class => 'onIdentifiersModifiedSinceSearchBuilderBuilt',
        ];    
    }
    
    public function onIdentifiersModifiedSinceSearchBuilderBuilt(IdentifiersModifiedSinceSearchBuilderBuiltEvent $event): void
    {
        if (!$event->getImporter() instanceof ProductImporter &&
            !$event->getImporter() instanceof ProductAssociationsImporter) {
            return;
        }

        $searchBuilder = $event->getSearchBuilder();
        $searchBuilder->addFilter('family', 'NOT EMPTY');
        $searchBuilder->addFilter('completeness', '=', 100, ['scope' => 'ecommerce']);
    }
}
```

### Customize Akeneo products to import from the webhook

By default, the webhook is configured to import all the Akeneo products and product model that have been created or
updated on Akeneo. But maybe you want to only import a subset of those Akeneo entities. In this case you can define an
event listener or subscriber for the `Webgriffe\SyliusAkeneoPlugin\Event\AkeneoProductChangedEvent` event and change the
value of the property ignorable of the event based on given product:

```php
// src/EventSubscriber/AkeneoProductChangedEventSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer as ProductAssociationsImporter;

final class AkeneoProductChangedEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AkeneoProductChangedEvent::class => 'onAkeneoProductChanged',
        ];    
    }
    
    public function onAkeneoProductChanged(AkeneoProductChangedEvent $event): void
    {
        $akeneoProduct = $event->getAkeneoProduct();
        if ($akeneoProduct['family'] === null) {
            $event->setIgnorable(true);
        }
    }
}
```

The same can be applied to the `Webgriffe\SyliusAkeneoPlugin\Event\AkeneoProductModelChangedEvent` event.
