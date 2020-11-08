<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Akeneo Plugin</h1>
<p align="center">Plugin allowing to import data from Akeneo PIM to your Sylius store.</p>
<p align="center"><a href="https://travis-ci.org/webgriffe/SyliusAkeneoPlugin"><img src="https://travis-ci.org/webgriffe/SyliusAkeneoPlugin.svg?branch=master" alt="Build Status" /></a></p>


## Requirements

* PHP `^7.3`
* Sylius `^1.7`
* Akeneo PIM CE or EE `>= 3.2`.
  The requirement for the version `3.2` is because the provided implementation of the product importer relies on the `family_variant` key in the Akeneo [GET Product model](https://api.akeneo.com/api-reference.html#get_product_models__code_) API response.

## Installation

1. Run `composer require webgriffe/sylius-akeneo-plugin`.

2. Add the plugin to the `config/bundles.php` file:

    ```php
    Webgriffe\SyliusAkeneoPlugin\WebgriffeSyliusAkeneoPlugin::class => ['all' => true],
    ```

3. Finish the installation by updating the database schema and installing assets:

    ```bash
    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate
    bin/console assets:install
    bin/console sylius:theme:assets:install
    ```

## Configuration

First of all you must configure your Akeneo API connection parameters. Create a file in `config/packages/webgriffe_sylius_akeneo_plugin.yaml` with the following content:

```yaml
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

Then you'll need to configure the product importer **value handlers**. In the same file `config/packages/webgriffe_sylius_akeneo_plugin.yaml` add the following:

```yaml
webgriffe_sylius_akeneo:
  # ...

  value_handlers:
    product:
      name:
        type: 'translatable_property'
          # The 'translatable_property' value handler will take values from
          # the provided Akeneo attribute and will set them to the provided
          # Sylius property path relative to both Product and Product
          # Variant tranlsations.
        options:
          akeneo_attribute_code: 'name'
            # The Akeneo attribute code where product names are stored.
          sylius_translation_property_path: 'name'
            # The Sylius product (and product variant) trslations property
            # path of property where to store the product names. It should
            # always be set to 'name' unless you have customized Sylius.
      slug:
        type: 'immutable_slug'
          # The 'immutable_slug' value handler will take values from the
          # provided Akeneo attribute and set the sluggified version of
          # that value on the Sylius slug property.
        options:
          akeneo_attribute_code: 'name'
            # The Akeneo attribute to sluggify and set to the Sylius slug
            # property.
      product_option:
        type: 'product_option'
          # The 'product_option' value handler sets Sylius product options
          # values for Product Variants which are part of configurable
          # products.
      price:
        type: 'channel_pricing'
          # The 'channel_pricing' value handler will take values from the
          # provided Akeneo attribute and will set them to the Sylius
          # product channel pricing.
        options:
          akeneo_attribute_code: 'price'
            # The Akeneo attribute code where prices are stored.
```

On a base Sylius installation without any customization these are the minimum required value handlers that you'll need to configure. In a real-world project you'll probably need to configure more value handlers. Every value handler must have a `type` and some `options` depending on the type itself. You can also specify a `priority` that will be used when adding that handler in the **value handlers resolver**. We'll cover **value handlers**  and its resolver later in this document.

## Usage

To import data you must first create queue items with the **enqueue command** and then you can import them with the **consume command**.

### Enqueue command

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

This way the enqueue command is run repeatedly enqueing only producs modified since the last command execution.

### Consume command

To import the Akeneo entities that are in the queue you can use the `webgriffe:akeneo:consume` console command:

```bash
bin/console webgriffe:akeneo:consume
```

This will consume all queue items which are not imported yet.

Of course you can put this command in cron as well:

```
*  * * * * /usr/bin/php /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:consume
```

## Architecture & customization

This plugin has basically two main entry points:

* The `Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand` which is responsible to put in a queue those Akeneo entity identifiers which need to be imported.
* The `Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand` which is responsible to load (or consume) all queue items that have not been imported yet and import the related Akeneo entities.

To be able to import different entities (or even only different parts of each entity), both commands use an **importer registry** which holds all the registered **importers**.

An importer is a service implementing the `Webgriffe\SyliusAkeneoPlugin\ImporterInterface` and mainly holds the logic about how to import its Akeneo entities. If you want to import from Akeneo other entities not implemented in this plugin you have can implement your own importer. You can also replace an importer provided with this plugin by decorating or replacing its service definition.

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

Akeneo is a Product Information Management system so its job is to manage product data. For this reason, this Sylius Akeneo plugin it's focused on importing products and provides a **product importer** (`\Webgriffe\SyliusAkeneoPlugin\Product\Importer`).

This product importer process Akeneo product data through the following several components.

#### Taxons resolver

A **taxons resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\TaxonsResolverInterface`) which is responsible to return the list of Sylius taxons for a given Akeneo product. The provided implementation of the taxons resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AlreadyExistingTaxonsResolver` class which returns the already existent Sylius taxons which have the same code as the related Akeneo categories.

#### Product options resolver

A **product options resolve** (`Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolverInterface`) which is responsible to return the related Sylius's **product option(s)** when the product being imported is part of a parent product model. The provided implementation of the product options resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver` class, which returns already existend product options but also create new ones if needed.

#### Channels resolver

A **channels resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\ChannelsResolverInterface`) which si responsible to return the list of Sylius channels where the products should be enabled. The provided implementation of the channels resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AllChannelsResolver` class which simply enables the product to all available Sylius channels.

#### Status resolver

A **status resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface`) which is responsible to return the Sylius product status (enabled or disabled, true or false). The provided implementation of the status resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\StatusResolver` which returns the same produc status of the related Akeneo product but only if this doesn't belong to a parent product model, otherwise it will always return true (enabled). This is because  in Sylius the product status is at product level and not (also) at product variant level; instead in Akeneo the status is only at product level and not at product model level. So, in Akeneo, you could have only one disabled product variant for a parent product which have several other variants enabled. This situation couldn't be mapped currently on Sylius.

#### Value handlers resolver

A **value handlers resolver** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface`) which is responsible to return a list of **value handlers** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface`) for each Akeneo product attribute value.

The provided implementation of the value handlers resolver is the `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` which returns, for each attribute value, the list of all the value handlers supporting that attribute value sorted by a priority. Each value handler returned by the resolver for a given attribute is then called to handle that value.

For more detail on how the Product importer works look at the code of the `Webgriffe\SyliusAkeneoPlugin\Product\Importer::import()` method.

#### Value handlers

By default, the provided `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` is configured with value handlers specified in the `webgriffe_sylius_akeneo.value_handlers.product` array as explained in the configuration paragraph. This plugin already provides some value handler implementations that are:

* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler` (type `channel_pricing`): it sets  the value found on a given Akeneo price attribute (`options.akeneo_attribute_code`) as the Sylius product's channels price for channels which the base currency is the price currency of the Akeneo price.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler` (type `image`): it downloads  the image found on a given Akeneo image attribute (`options.akeneo_attribute_code`) and sets it as a Sylius product image with a provided type string (`options.sylius_image_type`).
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler` (type `immutable_slug`): it slugifies the value found on a given Akeneo attribute (`options.akeneo_attribute_code`) and sets it on the Sylius slug product translation property.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler` (type `product_option`): it sets the value found on a given Akeneo attribute as a Sylius product option value on the product variant.
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler` (type `translatable_property`): using the [Symofony's Property Access component](https://symfony.com/doc/current/components/property_access.html), it sets the value found on a given Akeneo attribute (`options.akeneo_attribute_code`) on a given property path (`options.sylius_translation_property_path`) of both product and product variant translations.

To add a custom value handler to the resolver you can implement your own by implementing the `Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface` and then tag it with the `webgriffe_sylius_akeneo.product.value_handler` tag:

```yaml
# config/services.yaml
app.my_custom_value_handler:
  class: App\MyCustomValueHandler
  tags:
    - { name: 'webgriffe_sylius_akeneo.product.value_handler', priority: 42 }
```

### Product associations importer

Another provided importer is the **product associations importer** (`Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer`). This importer imports the Akeneo products associations to the analog Sylius products associations. The association types must already exist on Sylius with the same code they have on Akeneo.

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
    vendor/bin/ecs check src/ spec/ tests/
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
  vendor/bin/behat --strict --tags="~@javascript"
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
