<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\ProductModel\Importer;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.product_model.importer', Importer::class)
        ->args([
            service('webgriffe_sylius_akeneo.api_client'),
            service('event_dispatcher'),
            service('webgriffe_sylius_akeneo.command_bus'),
        ])
        ->tag('webgriffe_sylius_akeneo.importer')
    ;
};
