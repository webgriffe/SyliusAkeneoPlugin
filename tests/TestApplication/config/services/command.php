<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\Webgriffe\SyliusAkeneoPlugin\Command\AttributesImportCommand;
use Tests\Webgriffe\SyliusAkeneoPlugin\Command\TaxaImportCommand;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('app.command.attributes_import', AttributesImportCommand::class)
        ->args([
            service('sylius.factory.product_attribute'),
            service('sylius.repository.product_attribute'),
            service('sylius.provider.locale.channel_based.inner'),
            service('sylius.factory.product_attribute_translation'),
        ])
        ->tag('console.command')
    ;

    $services->set('app.command.taxons_import', TaxaImportCommand::class)
        ->args([
            service('sylius.factory.taxon'),
            service('sylius.repository.taxon'),
            service('sylius.factory.taxon_translation'),
            service('sylius.generator.taxon_slug'),
            service('sylius.provider.locale.channel_based.inner'),
        ])
        ->tag('console.command')
    ;
};
