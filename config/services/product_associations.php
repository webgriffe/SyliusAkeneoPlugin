<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.product_associations.importer', Importer::class)
        ->args([
            service('webgriffe_sylius_akeneo.api_client'),
            service('sylius.repository.product'),
            service('sylius.repository.product_association'),
            service('sylius.repository.product_association_type'),
            service('sylius.factory.product_association'),
            service('event_dispatcher'),
            service('sylius.repository.product_variant'),
        ])
        ->tag('webgriffe_sylius_akeneo.importer')
    ;
};
