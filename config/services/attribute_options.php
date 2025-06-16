<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\AttributeOptions\Importer;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.attribute_options.importer', Importer::class)
        ->args([
            service('webgriffe_sylius_akeneo.api_client'),
            service('sylius.repository.product_attribute'),
            service('event_dispatcher'),
            service('sylius.repository.product_option'),
            service('sylius.provider.translation_locale.admin'),
            service('sylius.factory.product_option_value_translation'),
            service('sylius.factory.product_option_value'),
            service('sylius.factory.product_option_translation'),
            service('translator'),
        ])
        ->tag('webgriffe_sylius_akeneo.importer')
    ;
};
