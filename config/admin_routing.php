<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_akeneo_product_import', '/product/{productId}/import')
        ->controller(['webgriffe_sylius_akeneo.controller.product_import_controller', 'importAction'])
    ;

    $routes->import(
        '@WebgriffeSyliusAkeneoPlugin/Resources/config/admin_routing.yml',
        'sylius_admin'
    );
    $routes->import('.', 'sylius.resource');
};
