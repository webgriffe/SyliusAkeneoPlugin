---
title: Contributing
layout: page
nav_order: 7
---

# Contributing

To contribute to this plugin clone this repository, create a branch for your feature or bugfix, do your changes and then
make sure all tests are passing.

## Setting up Sylius locally

### Installing Sylius and prepare database

#### Traditional way

From the directory of the plugin run the following commands:

```bash
composer install
(cd tests/Application && yarn install)
(cd tests/Application && yarn build)
(cd tests/Application && APP_ENV=test bin/console assets:install public)
```

To be able to run test you need a local database, you can use one locally installed on your machine 
and changing the parameters on your `tests/Application/.env.test.local` file or you can use Docker.
You can use the docker compose sample template to only run mysql on Docker and leaving runtime on local PHP:
    
```bash
cp docker-compose.override.sample.yml docker-compose.override.yml
```

Adjust your `tests/Application/.env.test.local` and then run the following commands:

```bash
(cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
(cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
```

#### Docker way

1. Execute `docker compose up -d`

2. Initialize plugin `docker compose exec sylius-app make init`

3. See your browser `open localhost`

### Running plugin tests

- Code style
  
    ```bash
    vendor/bin/ecs check src/ tests/Behat tests/Integration
    ```

- Static analysis
  
    ```bash
    vendor/bin/phpstan analyse -c phpstan.neon
    ```
  
    ```bash
    vendor/bin/psalm
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
  vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
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
        vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
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

## How install a local version of Akeneo PIM

Install Akeneo PIM 

```shell
composer create-project akeneo/pim-community-standard tests/PIM "7.0.*@stable" --ignore-platform-req=ext-apcu --ignore-platform-req=ext-imagick
```

Launch installation with docker (the first time it will take a while... Consider having a coffee ;-)).

```shell
cd tests/PIM && make prod
```
After all, we suggest to load a more fully loaded fixture than the minimal loaded by the prod env, launch the following command:
    
```shell
docker-compose run -u www-data --rm php php bin/console pim:installer:db --catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/icecat_demo_dev
```

If you already have installed the PIM and you want just to start this launch the following commands:

```shell
cd tests/PIM && make up
```

Now you can access to the PIM on http://localhost:8080/ with admin/admin as credentials.

After login go to Exports and launch a CSV export of categories and attributes (use the demo CSV downloads).
Remember to launch in a cli shell the following command to launch a messenger consumer:

```shell
docker-compose run -u www-data --rm php php bin/console messenger:consume import_export_job
```
Then import attributes on local Sylius installation by launching the command:
    
```shell
bin/console app:attributes-import path/to/attribute.csv
```

Import categories on local Sylius installation by launching the command:

```shell
bin/console app:taxa-import 
```

Then create a new connection in Connect > Connection settings with type data destination and save the credentials in you .env.local.

```dotenv
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_BASE_URL=http://localhost:8080/
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_CLIENT_ID=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_SECRET=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_USERNAME=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_PASSWORD=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_WEBHOOK_SECRET=WEBHOOK_SECRET
```

Now, if you want you can import products from Akeneo to Sylius by launching the command:

```shell
bin/console webgriffe:akeneo:import --all
bin/console messenger:consume main
```
