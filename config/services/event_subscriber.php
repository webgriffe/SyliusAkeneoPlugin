<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\EventSubscriber\ProductEventSubscriber;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.event_subscriber.product', ProductEventSubscriber::class)
        ->tag('kernel.event_subscriber')
    ;
};
