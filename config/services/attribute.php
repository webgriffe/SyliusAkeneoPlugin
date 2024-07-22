<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Attribute\Importer;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.attribute.importer', Importer::class)
        ->args([
            service('event_dispatcher'),
            service('webgriffe_sylius_akeneo.api_client'),
            service('sylius.repository.product_option'),
            service('sylius.factory.product_option_translation'),
            service('sylius.repository.product_attribute'),
            service('sylius.factory.product_attribute_translation'),
        ])
        ->tag('webgriffe_sylius_akeneo.importer')
    ;
};
