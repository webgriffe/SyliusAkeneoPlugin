---
title: Webhook
layout: page
nav_order: 5
---

{% raw %}

# Webhook

This plugin provides a webhook that can be used to automatically import products from Akeneo PIM to Sylius when they are
created or updated.
To use the webhook you need to:

1. Import the routes needed for the plugin by adding the following to your `config/routes.yaml` file:
    ```yaml
    webgriffe_sylius_akeneo_plugin_webhook:
        resource: "@WebgriffeSyliusAkeneoPlugin/config/routes/webhook.php"
        prefix: ''
    ```
   The url of the webhook can be anything you want but it must be the same you will configure in Akeneo PIM. The
   imported resource will use /akeneo/webhook, but if you prefer you can add any prefix you want or you can completely
   rewrite the url:
    ```yaml
    webgriffe_sylius_akeneo_plugin_webhook:
        path: /akeneo/complete/url/rewrite/webhook
        methods: [POST]
        controller: webgriffe_sylius_akeneo.controller.webhook::postAction
    ```
2. Configure the webhook in Akeneo PIM. Remember that events API are available from Akeneo 5. You can find the webhook
   configuration in the Akeneo PIM's
   menu: `Connect > Connection settings`. Select the current data destination connection (the one used from the plugin).
   Now, select Event subscription from the left menu.
   Check Event subscription activation and leave unchecked Use product UUID instead of product identifier? (this is not
   currently supported). Now is time to insert the full URL previously configurated.
   When you click the Save button, a new secret token will be generated. Copy it and paste it in the plugin's
   configuration (see next step).
   ![akeneo-event-subscrition.png](images%2Fakeneo-event-subscrition.png)
3. In the plugin configuration (probably in the file config/packages/webgriffe_sylius_akeneo_plugin.yaml) add the
   following:
    ```yaml
    webhook:
        secret: 'YOUR_TOKEN_VALUE'
    ```
   Replace YOUR_TOKEN_VALUE with the secret token generated previously by Akeneo PIM. As always, we suggest to add this
   token by using an env variable to keep it secret from the repository (
   see [Symfony best practices doc](https://symfony.com/doc/current/best_practices.html#configuration)).
4. If you want, you can now TEST the webhook with the dedicated button on Akeneo event subscription page. If any error
   occurs, you can debug the webhook by adjusting the monolog.logger.webgriffe_sylius_akeneo_plugin monolog level to
   debug, so that you will see if there is something that is currently not working.
5. Finally, it is highly suggested that you remove the Product and ProductAssociations importer from the crontab to
   avoid products imported twice:
   ```diff
    - *   *   *  *  *  /path/to/sylius/bin/console -e prod -q webgriffe:akeneo:import --since-file=/path/to/sylius/var/storage/akeneo-import-sincefile.txt --importer="Product" --importer="ProductAssociations"
    ```

{% endraw %}
 
