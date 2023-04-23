# Contributing

## How install a local version of Akeneo PIM

Install Akeneo PIM 

```shell
composer create-project akeneo/pim-community-standard tests/PIM "7.0.*@stable" --ignore-platform-req=ext-apcu --ignore-platform-req=ext-imagick
```

Launch installation with docker (the first time it will take a while... Consider having a coffee ;-)).

```shell

```shell
cd tests/PIM && make prod
```
After all, we suggest to load a more fully loaded fixture than the minimal loaded by the prod env, launch the following command:
    
```shell
docker-compose run -u www-data --rm php php bin/console pim:installer:db --catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/icecat_demo_dev
```

Now you can access to the PIM on http://localhost:8080/ with admin/admin as credentials.

After login go to Exports and launch a CSV export of categories and attributes.
Remember to launch in a cli shell the following command to launch a messenger consumer:

```shell
docker-compose run -u www-data --rm php php bin/console messenger:consume import_export_job
```

Then create a new connection in Connect > Connection settings with type data destionation.
Then save the credentials

CLIENT_ID=1_16b6y9ozt8qo88sk0gk04sowwo4ggogwc8wo0gw0k04gkk4cwk
SECRET=3a68mlnkqcowosog4888wssg0c4ksg44g40g4sk840sgk8kkcw
USERNAME=sylius_7489
PASSWORD=4861c8394

