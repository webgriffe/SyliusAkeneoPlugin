<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Akeneo Plugin</h1>
<p align="center">Plugin allowing to import data from Akeneo PIM to your Sylius store.</p>
<p align="center"><a href="https://travis-ci.org/webgriffe/SyliusAkeneoPlugin"><img src="https://travis-ci.org/webgriffe/SyliusAkeneoPlugin.svg?branch=master" alt="Build Status" /></a></p>
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

## Minimum configuration

First of all you must configure your Akeneo API connection parameters:

```yaml
parameters:
    # ...
    # These values are from the official Akeneo PIM demo, replace with yours.
    webgriffe_sylius_akeneo.api_client.base_url: http://demo.akeneo.com/
    webgriffe_sylius_akeneo.api_client.username: admin
    webgriffe_sylius_akeneo.api_client.password: admin
    webgriffe_sylius_akeneo.api_client.client_id: 1_demo_client_id
    webgriffe_sylius_akeneo.api_client.secret: demo_secret
```

Pay attention that among these parameters there are some sensitive configuration that you probably don't want to commit in your VCS. There are different solutions to this problem, like env configurations and secrets. Refer to [Symfony best practices doc](https://symfony.com/doc/current/best_practices.html#configuration) for more info.

Then you'll probably need to configure other services like **value handlers** that we'll cover later in this document.

## Main concepts

This plugin has basically two main entry points:

* The `Webgriffe\SyliusAkeneoPlugin\Command\EnqueueCommand` which is responsible to put in a queue those Akeneo entity identifiers which need to be imported.
* The `Webgriffe\SyliusAkeneoPlugin\Command\ConsumeCommand` which is responsible to load (or consume) all queue items that have not been imported yet and import the related Akeneo entities.

To be able to import different entities (or even only different parts of each entity), both commands use an **importer registry** which holds all the registered **importers**.

An importer is a service implementing the `Webgriffe\SyliusAkeneoPlugin\ImporterInterface` and mainly holds the logic about how to import its Akeneo entities. If you want to import from Akeneo other entities not implemented in this plugin you have "only" to implement your own importer and add it to the importer registry. You can also replace an importer provided with this plugin by decorating or replacing its service definition.

## Product Importer

Akeneo is a Product Information Management system so its job is to manage product data. For this reason, this Sylius Akeneo plugin it's focused on importing products and provides a **product importer** (`\Webgriffe\SyliusAkeneoPlugin\Product\Importer`).

This product importer process Akeneo product data through the following several components.

### Categories handler

A **categories handler** (`Webgriffe\SyliusAkeneoPlugin\Product\CategoriesHandlerInterface`) which is responsible to associate imported products with their categories. The provided implementation of the categories handler is the `Webgriffe\SyliusAkeneoPlugin\Product\CategoriesHandler` class which associate the product to the already existent Sylius taxons which have the same code as the related Akeneo categories.

### Family variant handler

A **family variant handler** (`Webgriffe\SyliusAkeneoPlugin\Product\FamilyVariantHandlerInterface`) which, given an Akeneo family variant, is responsible to set the related Sylius's **product option(s)** on configurable products. The provided implementation of the family variant handler is the `Webgriffe\SyliusAkeneoPlugin\Product\FamilyVariantHandler` class, look at its code for more information.

### Channels resolver

A **channels resolver** (`Webgriffe\SyliusAkeneoPlugin\Product\ChannelsResolverInterface`) which si responsible to return the list of Sylius channels where the products should be enabled. The provided implementation of the channels resolver is the `Webgriffe\SyliusAkeneoPlugin\Product\AllChannelsResolver` class which simply enables the product to all available Sylius channels.

### Value handlers resolver

A **value handlers resolver** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface`) which is responsible to return a list of **value handlers** (`Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface`) for each Akeneo product attribute value.

The provided implementation of the value handlers resolver is the `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` which returns, for each attribute value, the list of all the value handlers supporting that attribute value sorted by a priority.

For more detail on how the Product importer works look at the code of the `Webgriffe\SyliusAkeneoPlugin\Product\Importer::import()` method.

### Value handlers

By default, the provided `Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver` is configured without any value handler. This means that no Akeneo product attribute value will be imported. If you want to start to import the Akeneo products attributes values you have to add to this resolver some value handlers. This plugin already provides some value handler implementations but you can easily implement your own by implementing the `Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface`. The provided value handlers implementation are:

* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler`
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler`
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler`
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler`
* `Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler`

## Contributing

To contribute to this plugin clone this repository, create a branch for your feature or bugfix, do your changes and then make sure al tests are passing.

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
  vendor/bin/behat --tags="~@javascript"
  ```

- Behat (JS scenarios)

  1. Download [Chromedriver](https://sites.google.com/a/chromium.org/chromedriver/)

  2. Download [Selenium Standalone Server](https://www.seleniumhq.org/download/).

  2. Run Selenium server with previously downloaded Chromedriver:

      ```bash
      java -Dwebdriver.chrome.driver=chromedriver -jar selenium-server-standalone.jar
      ```

  3. Run test application's webserver on `localhost:8080`:

      ```bash
      (cd tests/Application && bin/console server:run localhost:8080 -d public -e test)
      ```

  4. Run Behat:

      ```bash
      vendor/bin/behat --tags="@javascript"
      ```

### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && bin/console sylius:fixtures:load -e test)
    (cd tests/Application && bin/console server:run -d public -e test)
    ```

- Using `dev` environment:

    ```bash
    (cd tests/Application && bin/console sylius:fixtures:load -e dev)
    (cd tests/Application && bin/console server:run -d public -e dev)
    ```
