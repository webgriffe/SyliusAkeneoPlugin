---
title: Configuration
layout: page
nav_order: 3
---

# Configuration

First you must configure your Akeneo API connection parameters. Edit
the `config/packages/webgriffe_sylius_akeneo_plugin.yaml` file by adding the following content:

```yaml
# ...

webgriffe_sylius_akeneo:
    api_client:
        # These values are from the official Akeneo PIM demo, replace with yours.
        base_url: 'https://demo.akeneo.com/'
        username: 'admin'
        password: 'admin'
        client_id: '1_demo_client_id'
        secret: 'demo_secret'
```

Pay attention that among these parameters there are some sensitive configuration that you probably don't want to commit
in your VCS. There are different solutions to this problem, like env configurations and secrets. Refer
to [Symfony best practices doc](https://symfony.com/doc/current/best_practices.html#configuration) for more info.

To be able to see Akeneo item import results in Sylius backend (see [Usage](usage.md) section) you should enable our dedicated Symfony Messenger middleware for the `sylius.command_bus`.
To do so you have to add the following configuration in `config/framework.yaml`:

```yaml
# ...

framework:
    # ...
    messenger:
        # ...
        sylius.command_bus:
            middleware:
                - 'webgriffe_sylius_akeneo.middleware.item_import_result_persister'
                # The following middlewares should be copied and pasted from sylius.command_bus middlewares defined in
                # vendor/sylius/sylius/src/Sylius/Bundle/CoreBundle/Resources/config/app/messenger.yaml
                - 'validation'
                - 'doctrine_transaction'        
```

Be aware to put the `webgriffe_sylius_akeneo.middleware.item_import_result_persister` middleware before all other middlewares defined by Sylius core (you should copy and paste them from `vendor/sylius/sylius/src/Sylius/Bundle/CoreBundle/Resources/config/app/messenger.yaml`).
