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
