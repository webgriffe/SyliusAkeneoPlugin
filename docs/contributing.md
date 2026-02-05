---
title: Contributing
layout: page
nav_order: 7
---

# Contributing

To contribute to this plugin clone this repository, create a branch for your feature or bugfix, do your changes and then
make sure all tests are passing.
For a comprehensive guide on Sylius Plugins development please go to Sylius documentation,
there you will find the <a href="https://docs.sylius.com/plugins-development-guide/how-to-create-a-plugin-for-sylius">Plugin Development Guide</a> - it's a great place to start.
For more information about the **Test Application** included in this plugin, please refer to the [Sylius documentation](https://docs.sylius.com/plugins-development-guide/test-application).

## Setting up Sylius locally

### Installing Sylius and prepare database

#### Traditional way

1.  From the directory of the plugin run the following commands:
    ```bash
    composer install
    (cd vendor/sylius/test-application && yarn install)
    (cd vendor/sylius/test-application && yarn build)
    vendor/bin/console assets:install
    
    vendor/bin/console doctrine:database:create
    vendor/bin/console doctrine:migrations:migrate -n
    # Optionally load data fixtures
    vendor/bin/console sylius:fixtures:load -n
    ```
    
    To be able to run test you need a local database, you can use one locally installed on your machine 
    and changing the parameters on your `tests/Application/.env.test.local` file or you can use Docker.
    You can use the docker compose sample template to only run mysql on Docker and leaving runtime on local PHP:
    
    To be able to set up a plugin's database, remember to configure your database credentials in `tests/TestApplication/.env` and `tests/TestApplication/.env.test`.

2.  Run your local server:
    ```bash
    symfony server:ca:install
    symfony server:start -d
    ```

3.  Open your browser and navigate to `https://localhost:8000`.

#### Docker way

1.  Execute `make init` to initialize the container and install the dependencies.

2.  Execute `make database-init` to create the database and run migrations.

3.  (Optional) Execute `make load-fixtures` to load the fixtures.

4.  Your app is available at `http://localhost`.

### Running plugin tests

-   Code style
    ```bash
    vendor/bin/ecs check
    ```

-   Static analysis
    ```bash
    vendor/bin/phpstan analyse
    ```
    ```bash
    vendor/bin/psalm
    ```

-   PHPUnit
    ```bash
    vendor/bin/phpunit
    ```

-   PHPSpec
    ```bash
    vendor/bin/phpspec run
    ```

-   Behat (non-JS scenarios)
    ```bash
    vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
    ```

-   Behat (JS scenarios)
    1. [Install Symfony CLI command](https://symfony.com/download).

    2. Start Headless Chrome:
       ```bash
       google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
       ```

    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:
       ```bash
       symfony server:ca:install
       APP_ENV=test symfony server:start --port=8080 --daemon
       ```

    4. Run Behat:
       ```bash
       vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
       ```

### Opening Sylius with your plugin

-   Using `test` environment:
    ```bash
    APP_ENV=test vendor/bin/console vendor/bin/console sylius:fixtures:load -n
    APP_ENV=test symfony server:start -d
    ```

-   Using `dev` environment:
    ```bash
    vendor/bin/console vendor/bin/console sylius:fixtures:load -n
    symfony server:start -d
    ```

## How install a local version of Akeneo PIM and use it to test the plugin

### First time Akeneo PIM install

If your `tests/PIM` directory is empty you have to download and install Akeneo PIM.
To do so first run this command to download Akeneo:

```shell
rm tests/PIM/.gitkeep
composer create-project akeneo/pim-community-standard tests/PIM "7.0.*@stable"  --ignore-platform-req=php --ignore-platform-req=ext-apcu --ignore-platform-req=ext-imagick
```
Then launch Akeneo installation with docker (the first time it will take a while... Consider having a coffee 😉:

```shell
(cd tests/PIM && make prod)
```
After all, we suggest to load a more fully loaded fixture than the minimal loaded by the prod env, launch the following command:

```shell
(cd tests/PIM && docker-compose run -u www-data --rm php php bin/console pim:installer:db --catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/icecat_demo_dev)
```

### Using Akeneo test installation

If you already have installed the PIM and you want just to start it launch the following command:

```shell
(cd tests/PIM && make up)
```

Now you can access to the PIM on http://localhost:8080/ with admin/admin as credentials.

### Prepare Sylius installation to work with the Akeneo test installation

In Akeneo, go to Exports and launch a CSV export of categories and attributes (use the demo CSV downloads).
Remember to launch in a cli shell the following command to launch a messenger consumer:

```shell
(cd tests/PIM && docker-compose run -u www-data --rm php php bin/console messenger:consume import_export_job)
```
You can terminate that consumer when those exports are completed.

In Akeneo create a new connection in Connect > Connection settings with type data destination and save the credentials in your `tests/TestApplication/.env.local` file:

```dotenv
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_BASE_URL=http://127.0.0.1:8080/
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_CLIENT_ID=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_SECRET=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_USERNAME=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_PASSWORD=SAMPLE
WEBGRIFFE_SYLIUS_AKENEO_PLUGIN_WEBHOOK_SECRET=WEBHOOK_SECRET
```

Now you have to import the attributes and categories (taxa) from Akeneo on the local Sylius installation.
Start the local Sylius instance (refer [Opening Sylius with your plugin](#opening-sylius-with-your-plugin) paragraph to do so),
then clean it by loading the provided `akeneo` fixtures suite:

```shell
vendor/bin/console sylius:fixtures:load akeneo
```

Then you can proceed with the import of attributes:
    
```shell
vendor/bin/console app:attributes-import ~/Downloads/attribute.csv
```

And categories:

```shell
vendor/bin/console app:taxa-import ~/Downloads/category.csv
```

Now, if you want you can import products from Akeneo to Sylius by launching the command:

```shell
vendor/bin/console webgriffe:akeneo:import --all
vendor/bin/console messenger:consume main -vvv
```
