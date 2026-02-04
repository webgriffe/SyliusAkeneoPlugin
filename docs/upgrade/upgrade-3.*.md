---
title: Upgrade to 3.*
layout: page
nav_order: 0
parent: Upgrade
---

# Upgrade from `v2.9.x` to `v3.0`

In this version, we have updated the plugin to be fully compatible with version 2 of Sylius and to use the Sylius test application for plugin development and testing.

- The route `@WebgriffeSyliusAkeneoPlugin/config/admin_routing.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/routes/admin.php`.
- The route `@WebgriffeeSyliusAkeneoPlugin/config/webhook_routing.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/routes/webhook.php`.
- The route `@WebgriffeSyliusAkeneoPlugin/config/config.yaml` has been renamed to `@WebgriffeSyliusAkeneoPlugin/config/config.php`.
- The template `@WebgriffeSyliusAkeneoPlugin/ItemImportResult/Grid/Field/successful.html.twig` has been renamed to `@WebgriffeSyliusAkeneoPlugin/admin/item_import_result/grid/field/successful.html.twig`.
- The template `@WebgriffeSyliusAkeneoPlugin/Product/Grid/Action/import.html.twig` has been removed.
