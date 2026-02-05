---
title: Installation
layout: page
nav_order: 2
---

{% raw %}

# Installation

1. Run
   ```shell
   composer require webgriffe/sylius-akeneo-plugin akeneo/api-php-client
   ```

2. Add `Webgriffe\SyliusAkeneoPlugin\WebgriffeSyliusAkeneoPlugin::class => ['all' => true]` to your `config/bundles.php`.

   Normally, the plugin is automatically added to the `config/bundles.php` file by the `composer require` command. If it is not, you have to add it manually.

3. Add basic plugin configuration by creating the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file with the following content:
   ```yaml
   imports:
       - { resource: "@WebgriffeSyliusAkeneoPlugin/config/config.php" }
   ```

4. Import the routes needed for the plugin by adding the following to your `config/routes.yaml` file:
   ```yaml
   webgriffe_sylius_akeneo_plugin_admin:
       resource: "@WebgriffeSyliusAkeneoPlugin/config/routes/admin.php"
       prefix: '/%sylius_admin.path_name%'
   ```

5. Run the migrations to create the tables needed by the plugin:
   ```shell
   bin/console doctrine:migrations:migrate
   ```

6. Finish the installation by installing assets:
   ```bash
   bin/console assets:install
   bin/console sylius:theme:assets:install
   ```

7. _Optional (usually only on production or pre-production environments)_. Install
   the [suggested crontab](usage.html#suggested-crontab).

{% endraw %}
 
