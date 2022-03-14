<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Akeneo Plugin</h1>
<p align="center">Plugin allowing to import data from Akeneo PIM to your Sylius store.</p>
<p align="center"><a href="https://github.com/webgriffe/SyliusAkeneoPlugin/actions"><img src="https://github.com/webgriffe/SyliusAkeneoPlugin/workflows/Build/badge.svg" alt="Build Status" /></a></p>

## Requirements

* PHP `^8.0`
* Sylius `^1.11`
* Akeneo PIM CE or EE `>= 3.2`.
  The requirement for the version `3.2` is because the provided implementation of the product importer relies on the `family_variant` key in the Akeneo [GET Product model](https://api.akeneo.com/api-reference.html#get_product_models__code_) API response.

## Installation

1. Run `composer require webgriffe/sylius-akeneo-plugin`.

2. Add the plugin to the `config/bundles.php` file:

    ```php
    Webgriffe\SyliusAkeneoPlugin\WebgriffeSyliusAkeneoPlugin::class => ['all' => true],
    ```

3. Import the plugin config by creating a file in `config/packages/webgriffe_sylius_akeneo_plugin.yaml` with the following contents:

    ```yaml
    imports:
      - { resource: "@WebgriffeSyliusAkeneoPlugin/Resources/config/config.yaml" }
    ```

4. Import the plugin routes by creating a file in `config/routes/webgriffe_sylius_akeneo_plugin.yaml` with the follwing content:

    ```yaml
    webgriffe_sylius_akeneo_plugin_admin:
        resource: "@WebgriffeSyliusAkeneoPlugin/Resources/config/admin_routing.yaml"
        prefix: /admin
    ```

5. Finish the installation by updating the database schema and installing assets:

    ```bash
    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate
    bin/console assets:install
    bin/console sylius:theme:assets:install
    ```

6. _Optional_. If you want you can also add the Schedule Akeneo PIM import button in the product's detail and edit page.
Override Sylius template by create a new file in the folder: `templates/bundles/SyliusAdminBundle/Product/_showInShopButton.html.twig`. Copy the content from the original Sylius file and paste it in the new file. Finally, add the button to the bottom of the file.

        # ...

        <a class="ui labeled icon button violet" href="{{  path('webgriffe_sylius_akeneo_product_enqueue', {'productId': product.id }) }}" >  
          <i class="icon cloud download"></i>  
          {{ 'webgriffe_sylius_akeneo.ui.enqueue'|trans }}  
        </a>

7. _Optional (usually only on production or pre-production environments)_. Install the [suggested crontab](https://github.com/webgriffe/SyliusAkeneoPlugin#suggested-crontab).

## Configuration

First of all you must configure your Akeneo API connection parameters. Edit the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file by adding the following content:

```yaml
# ...

webgriffe_sylius_akeneo:
  api_client:
    # These values are from the official Akeneo PIM demo, replace with yours.
    base_url: 'http://demo.akeneo.com/'
    username: 'admin'
    password: 'admin'
    client_id: '1_demo_client_id'
    secret: 'demo_secret'
```

Pay attention that among these parameters there are some sensitive configuration that you probably don't want to commit in your VCS. There are different solutions to this problem, like env configurations and secrets. Refer to [Symfony best practices doc](https://symfony.com/doc/current/best_practices.html#configuration) for more info.

## Usage

Without any further action this plugin will not import anything from Akeneo. Depending on your import needs, after the initial configuration you have to configure specific product **value handlers** and create some Akeneo-related entities on Sylius.

### Importing product name, short description and description

If you want to import name, short description and description for your products you have to configure proper **translatable property value handlers** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      name:
        type: 'translatable_property'
        options:
          $akeneoAttributeCode: 'name'
          $translationPropertyPath: 'name'
      short_description:
        type: 'translatable_property'
        options:
          $akeneoAttributeCode: 'short_description'
          $translationPropertyPath: 'shortDescription'
      description:
        type: 'translatable_property'
        options:
          $akeneoAttributeCode: 'description'
          $translationPropertyPath: 'description'
```

For each `translatable_property` value handler you have to configure, in `$translationPropertyPath`, the Sylius product (and product variant) translation property path of the property where to store the value of the Akeneo attribute whose code is configured with `$akeneoAttributeCode`.

In the same way you can import other translatable properties values like meta keyword, meta description and other custom translatable properties you possibly added to your store.

### Importing product dimensions

If you want to import product dimensions like height, width, depth and weight you have to configure the proper **generic property value handlers** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      weight:
        type: 'generic_property'
        options:
          $akeneoAttributeCode: 'weight'
          $propertyPath: 'weight'
      depth:
        type: 'generic_property'
        options:
          $akeneoAttributeCode: 'depth'
          $propertyPath: 'depth'
      width:
        type: 'generic_property'
        options:
          $akeneoAttributeCode: 'width'
          $propertyPath: 'width'
      height:
        type: 'generic_property'
        options:
          $akeneoAttributeCode: 'height'
          $propertyPath: 'height'      
```

For each `generic_property` value handler you have to configure, in `$propertyPath`, the Sylius product property path of the property where to store the value of the Akeneo attribute whose code is configured with `$akeneoAttributeCode`.

In the same way you can import other product properties like shipping category and other custom properties you possibly added to your store.

### Importing product slug

This plugin is able to create a sluggified version of an Akeneo attribute value, often the product name, and import it into Sylius product slug. If the slug is already set on Sylius for a certain product it will not be changed during the import from Akeneo.

To enable this behavior you have to configure the **immutable slug value handler** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      slug:
        type: 'immutable_slug'
        options:
          $akeneoAttributeToSlugify: 'name'      
```

In the `$akeneoAttributeToSlugify` option you have to set the Akeneo attribute code that you want to sluggify and set in the Sylius product slug.

Otherwise, If you have a slug attribute directly on Akeneo, you can import it like any other translatable property using a **translatable property value handler**:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      slug:
        type: 'translatable_property'
        options:
          $akeneoAttributeCode: 'slug'
          $translationPropertyPath: 'slug'
```

### Importing product images

If you want to import product images from Akeneo you have to configure the **image value handler** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      main_image:
        type: 'image'
        options:
          $akeneoAttributeCode: 'main_image'
          $syliusImageType: 'main_image'
      secondary_image_1:
        type: 'image'
        options:
          $akeneoAttributeCode: 'secondary_image_1'
          $syliusImageType: 'secondary_image_1'
      secondary_image_2:
        type: 'image'
        options:
          $akeneoAttributeCode: 'secondary_image_2'
          $syliusImageType: 'secondary_image_2'
```

In the `$akeneoAttributeCode` option you have to set the code of the Akeneo attribute where you store product images and in the `$syliusImageType` you have to configure the string to set on Sylius as product image type.

### Importing product attributes values

This plugin will automatically create or update Sylius product attributes values during product import. All you have to do is to **create, on Sylius, the same product attributes that you have on Akeneo** paying attention to assign the same code they have on Akeneo and to choose a compatible type considering the type they have on Akeneo. For example, if you have a simple select attribute on Akeneo you should create it as a select attribute on Sylius; similarly if you have a text attribute on Akeneo you should create it as a text attribute on Sylius.

You're not forced to create on Sylius all the attributes you have on Akeneo but only those you need to be imported to your store.

Then to import the actual product attributes values you have to configure the **generic attribute value handler** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      attributes:
        type: 'generic_attribute'      
```

The `generic_attribute` value handler doesn't need any configuration; it must be configured only once and it will handle all the product attributes created on Sylius.

Also, keep in mind that **this plugin will import Sylius select attributes options** from Akeneo automatically, no configuration is needed.

#### Akeneo file attributes handling

This plugin is also able to handle Akeneo file attributes even if there is no corresponding file attribute on Sylius.

Suppose that you have a *Technical Sheet* file attribute on Akeneo (with code `technical_sheet`) and you want to make those technical sheets downloadable from the Sylius product page in the frontend. To do so, just **create a Sylius text attribute with the same code** of the Akeneo *Tehcnical Sheet* attribute. Then configure a `file_attribute` for that attribute and make sure that the `generic_attribute` value handler is configured:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      attributes:
        type: 'generic_attribute'
      technical_sheet:
        type: 'file_attribute'
        options:
          $akeneoAttributeCode: 'technical_sheet'
          $downloadPath: '%sylius_core.public_dir%/media/product_technical_sheets'
```

After a product import you'll have the products technical sheets downloaded in the `%sylius_core.public_dir%/media/product_technical_sheets` directory and the path of the techincal sheet file of each product saved in the `technical_sheet` text attribute (the path will be relative to the download path). So, in your product show template you can have the following to allow users to download technical sheets:

```twig
{# templates/SyliusShopBundle/views/Product/show.html.twig #}

{% set technicalSheet = product.getAttributeByCodeAndLocale('technical_sheet', sylius.localeCode) %}
{% if technicalSheet and technicalSheet.value is not empty %}
  <a href="{{ asset('media/product_technical_sheet/' ~ technicalSheet.value) }}">
    Download Techincal Sheet
  </a>
{% endif %}
```

### Importing configurable products, product options and their values

If you have product models on Akeneo this plugin will create relative configurable products along with their variants on Sylius. To make this possible **you must create on Sylius the product options with the same code of the related Akeneo's family variant axes attributes**. You can leave the product options empty (without values) because they will be created during actual product import. To do so you must configure the **product options value handler** in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml`:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      product_option:
        type: 'product_option'      
```

The `product_option` value handler doesn't need any configuration; it must be configured only once and it will handle all the product options created on Sylius.

### Importing product prices

If you manage product prices on Akeneo you can import them on Sylius. To do so you have to configure the channel pricing value handler in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      price:
        type: 'channel_pricing'
        options:
          $akeneoAttribute: 'price'      
```

In the `$akeneoAttribute` option you have to set the code of the **Akeneo price attribute** where you store your products prices. Then they will be imported into Sylius for channels whose base currency is the same as the price currency on Akeneo.

If you also manage the original price on Akeneo, you can import it using the same channel price value handler. Add the `$syliusPropertyPath` option to the configuration and specify which price you are importing: price (default) or original_price.
As above use the `$akeneoAttribute` option to specify the code of the **Akeneo original price attribute** where you store your products original prices.

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      price:
        type: 'channel_pricing'
        options:
          $akeneoAttribute: 'price'
          $syliusPropertyPath: 'price' # Not required, it is the default
      original_price:
        type: 'channel_pricing'
        options:
          $akeneoAttribute: 'original_price'
          $syliusPropertyPath: 'original_price'
```

### Importing product metrical properties

**NB. This feature is only available from Akeneo version 5**

If you manage product metrical attributes on Akeneo you can import them on Sylius as product properties (like weight, length, height and depth). To do this, you have to configure the metrical properties value handler in the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file:

```yaml
# config/packages/webgriffe_sylius_akeneo_plugin.yaml
webgriffe_sylius_akeneo:
  # ...
  value_handlers:
    product:
      # ...
      weight:
        type: 'metric_property'
        options:
          $akeneoAttributeCode: 'weight'
          $propertyPath: 'weight'
      height:
        type: 'metric_property'
        options:
          $akeneoAttributeCode: 'height'
          $propertyPath: 'height'
          $akeneoUnitMeasurementCode: 'CENTIMETER'
```

For each `metric_property` value handler you have to configure, in `$propertyPath`, the Sylius product property path of the property where to store the value of the Akeneo attribute whose code is configured with `$akeneoAttributeCode`. Be sure this is a metrical attribute on Akeneo.
In the same way you can import other product metrical properties like height and other custom properties you possibly added to your store.
In addition, you can decide in which unit of measure to import the value. To do this, enter the desired Akeneo unit of measurement code in the attribute `$akeneoUnitMeasurementCode`. If this field is not specified, the plugin will import the value using Akeneo's standard unit of measure.
For more information about Akeneo's units of measurement, consult the [documentation](https://help.akeneo.com/pim/serenity/articles/manage-your-measurements.html).

### Importing product-taxons associations

This plugin **will not import Akeneo categories into Sylius taxons**, but **it will associate Sylius products to existing taxons** based on Akeneo product-categories associations. This plugin will associate products only to those Sylius taxons which already exist on Sylius and have the same code of their related Akeneo categories. In this way, products taxons association import does not need any configuration and you can have all the categories you want on Akeneo, even those you don't want on your Sylius store. Indeed, if there are products associated to Akeneo categories which doesn't exist on Sylius, the import will succeed with no error.

So, all you have to do is to **create on Sylius those taxons that you want products associated with** when importing from Akeneo, paying attention to **assign the same code** of the corresponding category on Akeneo.

### Importing product associations

This plugin will also import product associations. It's a zero configuration import. All you have to do is to **create on Sylius the same association types that you have on Akeneo** paying attention to assign the same association type code. If you have some association type on Akeneo that you don't need on your store, simply do not create it on Sylius and product associations importer will ignore it.

### Launch data import from CLI

To actually import data you must first create queue items with the **enqueue command** and then you can import them with the **consume command**.

#### Schedule Akeneo PIM import button

This button allows you to queue a product directly from the admin index page. By adding the product to the queue, it will be updated by the Akeneo PIM at the next consume run.

![Schedule Akeneo PIM import button](schedule-akeneo-import-button.png)

#### Enqueue command

To create queue items you can use the `webgriffe:akeneo:enqueue` console command:

```bash
bin/console webgriffe:akeneo:enqueue --since="2020-01-30"
```

This will enqueue all Akeneo entities updated after the provided date.

You can also use a "since file" where to read the since date:

```bash
echo "2020-01-30" > var/storage/akeneo-sincefile.txt
bin/console webgriffe:akeneo:enqueue --since-file="var/storage/akeneo-sincefile.txt"
```

When run with the since file, the enqueue command will write the current date/time to the since file after the enqueueing process is terminated. This is useful when you put the  enqueue command in cron:

```
*  * * * * /usr/bin/php /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:enqueue --since-file=/path/to/sylius/var/storage/akeneo-enqueue-sincefile.txt
```

This way the enqueue command is run repeatedly enqueuing only producs modified since the last command execution.

You can also enqueue items only for specific importers:

```bash
bin/console webgriffe:akeneno:enqueue --importer="Product" --importer="MyImporter" --since="2020-01-30"
```

You can also enqueue items regardless of their last update date:

```bash
bin/console webgriffe:akeneno:enqueue --all
```

#### Consume command

To import the Akeneo entities that are in the queue you can use the `webgriffe:akeneo:consume` console command:

```bash
bin/console webgriffe:akeneo:consume
```

This will consume all queue items which are not imported yet.

Of course you can put this command in cron as well:

```
*  * * * * /usr/bin/php /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:consume
```

### Browsing queue items in the admin

You can examine the Akeneo import queue from the admin panel at **Catalog -> Akeneo PIM import**. You can filter and sort items and see their error message:

![Akeneo queue items grid](queue_items_grid.png)

### Queue clear

Sometimes it may be a good idea to clear the queue of imported items. This can also be useful in development to keep only items not imported due to errors or yet to be consumed.
You can clear the queue of elements by launching the following command:

```bash
bin/console webgriffe:akeneo:cleanup-queue {days}
```

Where {days} should be replaced by the number of days back from which to start deleting the queue of items.
If the number is not entered, the default value 10 will be used. 
So, if for example today is 2020-12-15 and you use the parameter days = 10, all the elements imported before 2020-12-05 will be deleted.

### Products reconciliation

Product reconciliation can be useful when one or more products are deleted on Akeneo. By default, reconciliation does not delete products on Sylius but places them in a deactivated state.
This is because the Sylius structure does not allow you to delete products if they are associated with any order.
To reconcile the products you can use the webgriffe:akeneo:reconcile console command:

    bin/console webgriffe:akeneo:reconcile

It could be useful to add also this command to your scheduler to run automatically every day or whatever you want.

### Suggested crontab

To make all importers and other plugin features work automatically the following is the suggested crontab:

```
0   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:enqueue --all --importer="AttributeOptions"
*   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:enqueue --since-file=/path/to/sylius/var/storage/akeneo-enqueue-sincefile.txt --importer="Product" --importer="ProductAssociations"
*   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:consume
0   0   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:cleanup-queue
0   */6 *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:reconcile
```

This will:
* Enqueue the update of all attribute options every hour
* Import, every minute, all products that have been modified since the last execution, along with their associations
* Clean the imported items queue older than 10 days, every day at midnight
* Reconcile Akeneo deleted products every 6 hours

Enqueue, Consume and Reconcile commands uses a [lock mechanism](https://symfony.com/doc/current/console/lockable_trait.html) which prevents running them if another instance of the same command is already running.

## Architecture & customization

This plugin has basically two main entry points:

* The `Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand` which is responsible to put in a queue those Akeneo entity identifiers which need to be imported.
* The `Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand` which is responsible to load (or consume) all queue items that have not been imported yet and import the related Akeneo entities.

To be able to import different entities (or even only different parts of each entity), both commands use an **importer registry** which holds all the registered **importers**.

An importer is a service implementing the `Webgriffe\SyliusAkeneoPlugin\ImporterInterface` and that mainly holds the logic about how to import its Akeneo entities. If you want to import from Akeneo other entities not implemented in this plugin you have can implement your own importer. You can also replace an importer provided with this plugin by decorating or replacing its service definition.

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
app.custom_importer:
  class: App\Importer\CustomImporter
  tags:
    - { name: 'webgriffe_sylius_akeneo.importer' }
```

Anyway, this plugin already implement the following importers.

### Product Importer

Akeneo is a Product Information Management system so its job is to manage product data. For this reason, this Sylius Akeneo plugin is focused on importing products and provides a **product importer** (`\Webgriffe\SyliusAkeneoPlugin\Product\Importer`).

This product importer processes Akeneo product data through the following components:

#### Taxons resolver

A **taxons resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\TaxonsResolverInterface`) which is responsible to return the list of Sylius taxons for a given Akeneo product. The provided implementation of the taxons resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AlreadyExistingTaxonsResolver` class which returns the already existent Sylius taxons which have the same code as the related Akeneo categories.

#### Product options resolver

A **product options resolve** (`Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolverInterface`) which is responsible to return the related Sylius's **product option(s)** when the product being imported is part of a parent product model. The provided implementation of the product options resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver` class, which returns already existend product options but also creates new ones if needed.

#### Channels resolver

A **channels resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\ChannelsResolverInterface`) which is responsible to return the list of Sylius channels where the products should be enabled. The provided implementation of the channels resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AllChannelsResolver` class which simply enables the product in all available Sylius channels.

#### Status resolver

A **status resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface`) which is responsible to return the Sylius product status (enabled or disabled, true or false). The provided implementation of the status resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\StatusResolver` which returns the same product status of the related Akeneo product, but only if this doesn't belong to a parent product model, otherwise it will always return true (enabled). This is because in Sylius the product status is at product level and not (also) at product variant level; instead, in Akeneo the status is only at product level and not at product model level. So, in Akeneo, you could have only one disabled product variant for a parent product which have several other variants enabled. This situation can't be mapped on Sylius at present.

#### Value handlers resolver

A **value handlers resolver** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface`) which is responsible to return a list of **value handlers** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface`) for each Akeneo product attribute value.

The provided implementation of the value handlers resolver is the `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` which returns, for each attribute value, the list of all the value handlers supporting that attribute value sorted by a priority. Each value handler returned by the resolver for a given attribute is then called to handle that value.

For more detail on how the Product importer works look at the code of the `Webgriffe\SyliusAkeneoPlugin\Product\Importer::import()` method.

#### Value handlers

By default, the provided `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` is configured with value handlers specified in the `webgriffe_sylius_akeneo.value_handlers.product` array as explained in the configuration paragraph. This plugin already provides some value handler implementations that are:

* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler` (type `channel_pricing`): it sets the value found on a given Akeneo price attribute (`options.$akeneoAttribute`) as the Sylius product's channels price for channels whose base currency is the price currency of the Akeneo price.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler` (type `image`): it downloads the image found on a given Akeneo image attribute (`options.$akeneoAttributeCode`) and sets it as a Sylius product image with a provided type string (`options.$syliusImageType`).
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler` (type `immutable_slug`): it slugifies the value found on a given Akeneo attribute (`options.$akeneoAttributeToSlugify`) and sets it on the Sylius slug product translation property.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler` (type `product_option`): it sets the value found on a given Akeneo attribute as a Sylius product option value on the product variant.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler` (type `translatable_property`): using the [Symofony's Property Access component](https://symfony.com/doc/current/components/property_access.html), it sets the value found on a given Akeneo attribute (`options.$akeneoAttributeCode`) on a given property path (`options.$translationPropertyPath`) of both product and product variant translations.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler` (type `generic_attribute`) it will automatically handle Sylius attributes whose attribute code matches Akeneo attribute code.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler` (type `file_attribute`): it saves the file downloaded from the Akeneo file attribute (`options.$akeneoAttributeCode`) to the given destination path (`options.$downloadPath`)

To add a custom value handler to the resolver you can implement your own by implementing the `Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface` and then tag it with the `webgriffe_sylius_akeneo.product.value_handler` tag:

```yaml
# config/services.yaml
app.my_custom_value_handler:
  class: App\MyCustomValueHandler
  tags:
    - { name: 'webgriffe_sylius_akeneo.product.value_handler', priority: 42 }
```

### Product associations importer

Another provided importer is the **product associations importer** (`Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer`). This importer imports the Akeneo products associations to the corresponding Sylius products associations. The association types must already exist on Sylius with the same code they have on Akeneo.

### Attribute options importer

Another provided importer is the **attribute options importer** (`\Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer`). This importer imports the Akeneo simple select and multi select attributes options into Sylius select attributes. The select attributes must already exist on Sylius with the same code they have on Akeneo.

## Contributing

To contribute to this plugin clone this repository, create a branch for your feature or bugfix, do your changes and then make sure al tests are passing.

    ```bash
    $ (cd tests/Application && yarn install)
    $ (cd tests/Application && yarn build)
    $ (cd tests/Application && APP_ENV=test bin/console assets:install public)
    
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
    ```

To be able to setup a plugin's database, remember to configure you database credentials in `tests/Application/.env` and `tests/Application/.env.test`.

### Running plugin tests

- Code style

  ```bash
  vendor/bin/ecs check src/ tests/Behat tests/Integration
  ```

- Static analysis

  ```bash
  vendor/bin/phpstan analyse -c phpstan.neon -l max src/
  ```

- PHPUnit

  ```bash
  vendor/bin/phpunit
  ```

- PHPSpec

  ```bash
  vendor/bin/phpspec run
  ```

- Behat (non-JS scenarios)

  ```bash
  vendor/bin/behat --strict --tags="~@javascript && ~@todo && ~@cli"
  ```

- Behat (JS scenarios)

  1. [Install Symfony CLI command](https://symfony.com/download).

  2. Start Headless Chrome:

      ```bash
      google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
      ```
  3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:

      ```bash
      symfony server:ca:install
      APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
      ```

  4. Run Behat:

      ```bash
      vendor/bin/behat --strict --tags="@javascript"
      ```

### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=test bin/console server:run -d public)
    ```

- Using `dev` environment:

    ```bash
    (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
    ```

## License

This plugin is under the MIT license. See the complete license in the LICENSE file.

## Credits

Developed by [Webgriffe®](http://www.webgriffe.com/).
