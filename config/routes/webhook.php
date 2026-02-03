<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_akeneo_webhook', '/akeneo/webhook')
        ->controller(['webgriffe_sylius_akeneo.controller.webhook', 'postAction'])
        ->methods(['POST'])
    ;
};
