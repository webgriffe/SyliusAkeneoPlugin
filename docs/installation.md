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

5. If you use `Doctrine` you should run a diff of your Doctrine's schema and then run the migration generated:
    ```shell
    bin/console doctrine:migrations:migrate
    ```

6. Finish the installation by installing assets:
    ```bash
    bin/console assets:install
    bin/console sylius:theme:assets:install
    ```

7. _Optional_. If you want you can also add the Import from Akeneo PIM button in the product's detail and edit page.
   Override Sylius template by create a new file in the
   folder: `templates/bundles/SyliusAdminBundle/Product/_showInShopButton.html.twig`. Copy the content from the original
   Sylius file and paste it in the new file. Finally, add the button to the bottom of the file.
    ```twig
        # ...

        <a class="ui labeled icon button violet" href="{{ path('webgriffe_sylius_akeneo_product_import', {'productId': product.id }) }}">
            <i class="icon cloud download"></i>  
            {{ 'webgriffe_sylius_akeneo.ui.import'|trans }}
        </a>
    ```

8. _Optional (usually only on production or pre-production environments)_. Install
   the [suggested crontab](usage.html#suggested-crontab).

{% endraw %}
 
