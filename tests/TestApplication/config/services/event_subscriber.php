<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\Webgriffe\SyliusAkeneoPlugin\EventSubscriber\IdentifiersModifiedSinceSearchBuilderBuiltEventSubscriber;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('app.command.attributes_import', IdentifiersModifiedSinceSearchBuilderBuiltEventSubscriber::class)
        ->args([

        ])
        ->tag('kernel.event_subscriber')
    ;
};
