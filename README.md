<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Akeneo Plugin</h1>
<p align="center">Plugin allowing to import products data from Akeneo PIM to your Sylius store.</p>

# This plugin is WORK IN PROGRESS!

Do not use or test it.

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



## Usage

### Running plugin tests

  - PHPUnit

    ```bash
    $ vendor/bin/phpunit
    ```

  - PHPSpec

    ```bash
    $ vendor/bin/phpspec run
    ```

  - Behat (non-JS scenarios)

    ```bash
    $ vendor/bin/behat --tags="~@javascript"
    ```

  - Behat (JS scenarios)

    1. Download [Chromedriver](https://sites.google.com/a/chromium.org/chromedriver/)
    
    2. Download [Selenium Standalone Server](https://www.seleniumhq.org/download/).
    
    2. Run Selenium server with previously downloaded Chromedriver:
    
        ```bash
        $ java -Dwebdriver.chrome.driver=chromedriver -jar selenium-server-standalone.jar
        ```
        
    3. Run test application's webserver on `localhost:8080`:
    
        ```bash
        $ (cd tests/Application && bin/console server:run localhost:8080 -d public -e test)
        ```
    
    4. Run Behat:
    
        ```bash
        $ vendor/bin/behat --tags="@javascript"
        ```

### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    $ (cd tests/Application && bin/console sylius:fixtures:load -e test)
    $ (cd tests/Application && bin/console server:run -d public -e test)
    ```
    
- Using `dev` environment:

    ```bash
    $ (cd tests/Application && bin/console sylius:fixtures:load -e dev)
    $ (cd tests/Application && bin/console server:run -d public -e dev)
    ```
