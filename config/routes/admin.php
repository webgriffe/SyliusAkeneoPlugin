<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_akeneo_product_import', '/product/{productId}/import')
        ->controller(['controller.product_import_controller', 'importAction'])
        ->methods(['GET'])
    ;

    $routes->import(<<<YAML
alias: webgriffe_sylius_akeneo.item_import_result
section: admin
path: akeneo_item_import_result
except: ['update', 'create', 'show']
templates: "@SyliusAdmin/shared/crud"
redirect: update
grid: webgriffe_sylius_akeneo_admin_item_import_result
vars:
    all:
        header: webgriffe_sylius_akeneo.ui.import
        subheader: webgriffe_sylius_akeneo.ui.view_import_history
    index:
        icon: 'cloud download'
YAML
, 'sylius.resource');
};
