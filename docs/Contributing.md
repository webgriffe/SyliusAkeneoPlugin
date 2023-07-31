# Contributing

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
```

Now, if you want you can import products from Akeneo to Sylius by launching the command:

```shell
bin/console webgriffe:akeneo:import --all
bin/console messenger:consume main
```
